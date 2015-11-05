<?php

/**
 * QuickfilePlugin.class.php
 *
 * @author  Florian Bieringer <florian.bieringer@uni-passau.de>
 */
class QuickfilePlugin extends StudIPPlugin implements SystemPlugin
{

    const USER_SEARCH = false;

    public function __construct()
    {
        parent::__construct();
        self::addStylesheet('/assets/style.less');
        PageLayout::addScript($this->getPluginURL() . '/assets/quickfile.js');
        PageLayout::addBodyElements('<div id="quickfilewrapper"><div id="quickfile"><h3>' . _('Dateisuche') . '</h3><div id="quickfileinput"><input type="text" placeholder="' . _('Veranstaltung / Datei') . '"></div><ul id="quickfilelist"></ul></div></div>');
    }

    public function find_action()
    {
        $search = trim(studip_utf8decode(Request::get('search')));

        // Filter for own courses
        if (!$GLOBALS['perm']->have_perm('admin')) {
            $ownseminars = "JOIN seminar_user ON (dokumente.seminar_id = seminar_user.seminar_id AND seminar_user.user_id = :userid) ";
        }

        if (self::USER_SEARCH) {
            $usersearch = "JOIN auth_user_md5 ON (dokumente.user_id = auth_user_md5.user_id)";
            $usercondition = " OR CONCAT_WS(' ', auth_user_md5.vorname, auth_user_md5.nachname, auth_user_md5.vorname) = :prequery ";
        }

        // Now check if we got a seminar
        if (strpos($search, '/') !== FALSE) {
            $args = explode('/', $search);
            $prequery = "%" . trim($args[0]) . "%";
            $query = "%" . trim($args[1]) . "%";
            $binary = '%' . join('%', str_split(strtoupper(trim($args[0])))) . '%';
            $comp = "AND";
        } else {
            $query = "%$search%";
            $prequery = $query;
            $comp = "OR";
            $binary = '%' . join('%', str_split(strtoupper($search))) . '%';
        }

        // Build query
        $sql = "SELECT dokumente.* FROM dokumente "
            . "JOIN seminare USING (seminar_id) $ownseminars $usersearch "
            . "WHERE (seminare.name LIKE BINARY :binary OR seminare.name LIKE :prequery $usercondition) "
            . "$comp dokumente.name LIKE :query "
            . "ORDER BY chdate DESC LIMIT 100";

        // now query
        $db = DBManager::get();
        $stmt = $db->prepare($sql);
        if (!$GLOBALS['perm']->have_perm('admin')) {
            $stmt->bindParam(':userid', User::findCurrent()->id);
        }
        $stmt->bindParam(':prequery', $prequery);
        $stmt->bindParam(':query', $query);
        $stmt->bindParam(':binary', $binary);
        $stmt->execute();
        $result = array();

        // Parse the results
        while (sizeof($result) < 5 && $data = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $file = StudipDocument::import($data);
            if ($file->checkAccess(User::findCurrent()->id)) {
                $result[] = array(
                    'id' => $file->id,
                    'name' => $this->mark($file->name, $search),
                    'filename' => $file->filename,
                    'course' => $this->mark($file->course ? $file->course->getFullname() . (self::USER_SEARCH ? " ({$file->author->getFullname()})" : '') : '', $search, false),
                    'date' => strftime('%x', $file->chdate)
                );
            }
        }

        // Send me an answer
        echo json_encode(studip_utf8encode($result));
        die;
    }

    private function mark($string, $query, $filename = true)
    {
        if (strpos($query, '/') !== FALSE) {
            $args = explode('/', $query);
            if ($filename) {
                return $this->mark($string, trim($args[1]));
            }
            return $this->mark($string, trim($args[0]));
        } else {
            $query = trim($query);
        }

        // Replace direct string
        $result = preg_replace("/$query/i", "<mark>$0</mark>", $string, -1, $found);
        if ($found) {
            return $result;
        }

        // Replace camelcase
        $replacement = "$" . (++$i);
        foreach (str_split(strtoupper($query)) as $letter) {
            $queryletter[] = "($letter)";
            $replacement .= "<mark>$" . ++$i . "</mark>$" . ++$i;
        }


        $pattern = "/([\w\W]*)" . join('([\w\W]*)', $queryletter) . "/";
        $result = preg_replace($pattern, $replacement, $string, -1, $found);
        if ($found) {
            return $result;
        }
        return $string;
    }

}
