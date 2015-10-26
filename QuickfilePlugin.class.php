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

    public function __construct() {
        parent::__construct();
        self::addStylesheet('/assets/style.less');
        PageLayout::addScript($this->getPluginURL() . '/assets/quickfile.js');
        PageLayout::addBodyElements('<div id="quickfilewrapper"><div id="quickfile"><h3>'._('Dateisuche').'</h3><div id="quickfileinput"><input type=text></div><ul id="quickfilelist"></ul></div></div>');
    }

    public function find_action() {
        $search = studip_utf8decode(Request::get('search'));
        
        // Filter for own courses
        if (!$GLOBALS['perm']->have_perm('admin')) {
            $user_id = User::findCurrent()->id;
            $ownseminars = "JOIN seminar_user ON (dokumente.seminar_id = seminar_user.seminar_id AND seminar_user.user_id = '{$user_id}') ";
        }

        // Now check if we got a seminar
        if (strpos($search, '/') !== FALSE) {
            $args = explode('/', $search);
            $binary = '%' . join('%', str_split(strtoupper($args[0]))) . '%';
            $sql = "SELECT dokumente.* FROM dokumente JOIN seminare USING (seminar_id) $ownseminars WHERE (seminare.name LIKE BINARY '{$binary}' OR seminare.name LIKE '%{$args[0]}%')  AND dokumente.name LIKE '%{$args[1]}%' ORDER BY chdate DESC LIMIT 100";
        } else {
            $sql = "SELECT dokumente.* FROM dokumente JOIN seminare USING (seminar_id) $ownseminars WHERE dokumente.name LIKE '%{$search}%' OR seminare.name LIKE '%{$search}%' ORDER BY chdate DESC LIMIT 1000";
        }

        // now query
        $db = DBManager::get();
        $stmt = $db->prepare($sql);
        $stmt->execute();
        $result = array();
        while (sizeof($result) < 5 && $data = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $file = StudipDocument::import($data);
            if ($file->checkAccess(User::findCurrent()->id)) {
                $result[] = array(
                    'id' => $file->id,
                    'name' => $file->name,
                    'filename' => $file->filename,
                    'course' => $file->course ? $file->course->getFullname() : '',
                    'date' => strftime('%x', $file->chdate)
                );
            }
        }

        // Send me an answer
        echo json_encode(studip_utf8encode($result));
        die;
    }

}
