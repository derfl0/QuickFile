<?php

/**
 * QuickfilePlugin.class.php
 *
 * ...
 *
 * @author  Florian Bieringer <florian.bieringer@uni-passau.de>
 * @version 0.1a
 */
class QuickfilePlugin extends StudIPPlugin implements SystemPlugin {
    
    const USER_SEARCH = false;

    public function __construct() {
        parent::__construct();
        self::addStylesheet('/assets/style.less');
        PageLayout::addScript($this->getPluginURL() . '/assets/quickfile.js');
        PageLayout::addBodyElements('<div id="quickfilewrapper"><div id="quickfile"><h3>'._('Dateisuche').'</h3><div id="quickfileinput"><input type=text></div><ul id="quickfilelist"></ul></div></div>');
    }

    public function find_action() {
        $search = studip_utf8decode(Request::get('search'));
        $query = "%$search%";
        
        // Filter for own courses
        if (!$GLOBALS['perm']->have_perm('admin')) {
            $ownseminars = "JOIN seminar_user ON (dokumente.seminar_id = seminar_user.seminar_id AND seminar_user.user_id = :userid) ";
        }
        
        if (self::USER_SEARCH) {
            $usersearch = "JOIN auth_user_md5 ON (dokumente.user_id = auth_user_md5.user_id)";
            $usercondition =  " OR CONCAT_WS(' ', auth_user_md5.vorname, auth_user_md5.nachname, auth_user_md5.vorname) = :prequery ";
        }

        // Now check if we got a seminar
        if (strpos($search, '/') !== FALSE) {
            $args = explode('/', $search);
            $prequery = "%{$args[0]}%";
            $query = "%{$args[1]}%";
            $binary = '%' . join('%', str_split(strtoupper($args[0]))) . '%';
            $comp = "AND";
        } else {
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
        while (sizeof($result) < 5 && $data = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $file = StudipDocument::import($data);
            if ($file->checkAccess(User::findCurrent()->id)) {
                $result[] = array(
                    'id' => $file->id,
                    'name' => $file->name,
                    'filename' => $file->filename,
                    'course' => $file->course ? $file->course->getFullname().(self::USER_SEARCH ? " ({$file->author->getFullname()})" : '') : '',
                    'date' => strftime('%x', $file->chdate)
                );
            }
        }

        // Send me an answer
        echo json_encode(studip_utf8encode($result));
        die;
    }

}
