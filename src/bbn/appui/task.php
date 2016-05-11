<?php
/**
 * Created by PhpStorm.
 * User: BBN
 * Date: 26/01/2015
 * Time: 05:45
 */

namespace bbn\appui;


class task {

  private static
    $cats = [],
    $actions = [],
    $states = [],
    $roles = [],
    $id_cat,
    $id_action,
    $id_state,
    $id_role;

  protected
    $db,
    $id_user,
    $is_admin,
    $user;


  private function _email($id_task, $subject, $text){
    /*
    $users = array_unique(array_merge($this->get_ccs($id_task), $this->mgr->get_users(1)));
    foreach ( $users as $u ){
      if ( ($u !== $this->id_user) && ($email = $this->mgr->get_email($u)) ){
        $this->db->insert('apst_accuses', [
          'email' => $email,
          'titre' => $subject,
          'texte' => $text,
          'etat' => 'pret'
        ]);
      }
    }
    */
  }

  private static function get_id_cat(){
    if ( !isset(self::$id_cat) && ($opt = \bbn\appui\options::get_options()) ){
      self::$id_cat = $opt->from_code('cats', 'bbn_tasks');
    }
    return self::$id_cat;
  }

  private static function get_id_action(){
    if ( !isset(self::$id_action) && ($opt = \bbn\appui\options::get_options()) ){
      self::$id_action = $opt->from_code('actions', 'bbn_tasks');
    }
    return self::$id_action;
  }

  private static function get_id_state(){
    if ( !isset(self::$id_state) && ($opt = \bbn\appui\options::get_options()) ){
      self::$id_state = $opt->from_code('states', 'bbn_tasks');
    }
    return self::$id_state;
  }

  private static function get_id_role(){
    if ( !isset(self::$id_role) && ($opt = \bbn\appui\options::get_options()) ){
      self::$id_role = $opt->from_code('roles', 'bbn_tasks');
    }
    return self::$id_role;
  }

  public static function get_cats($force = false){
    if ( empty(self::$cats) || $force ){
      if ( ($opt = \bbn\appui\options::get_options()) && self::get_id_cat() ){
        $tree = $opt->tree(self::$id_cat);
        self::$cats = isset($tree['items']) ? $tree['items'] : false;
      }
      else{
        self::$cats = false;
      }
    }
    return self::$cats;
  }

  public static function get_actions($force = false){
    if ( empty(self::$actions) || $force ){
      if ( ($opt = \bbn\appui\options::get_options()) && self::get_id_action() ){
        $actions = $opt->full_options(self::$id_action);
        foreach ( $actions as $action ){
          self::$actions[$action['code']] = $action['id'];
        }
      }
      else{
        self::$actions = false;
      }
    }
    return self::$actions;
  }

  public static function get_states($force = false){
    if ( empty(self::$states) || $force ){
      if ( ($opt = \bbn\appui\options::get_options()) && self::get_id_state() ){
        $states = $opt->full_options(self::$id_state);
        foreach ( $states as $state ){
          self::$states[$state['code']] = $state['id'];
        }
      }
      else{
        self::$states = false;
      }
    }
    return self::$states;
  }

  public static function get_roles($force = false){
    if ( empty(self::$roles) || $force ){
      if ( ($opt = \bbn\appui\options::get_options()) && self::get_id_role() ){
        $roles = $opt->full_options(self::$id_role);
        foreach ( $roles as $role ){
          self::$roles[$role['code']] = $role['id'];
        }
      }
      else{
        self::$roles = false;
      }
    }
    return self::$roles;
  }

  public static function get_cat($code, $force = false){
    if ( !isset(self::$cats[$code]) || $force ){
      self::get_cats(1);
      if ( !isset(self::$cats[$code]) ){
        self::$cats[$code] = false;
      }
    }
    return isset(self::$cats[$code]) ? self::$cats[$code] : false;
  }

  public static function get_action($code, $force = false){
    if ( !isset(self::$actions[$code]) || $force ){
      self::get_actions(1);
      if ( !isset(self::$actions[$code]) ){
        self::$actions[$code] = false;
      }
    }
    return isset(self::$actions[$code]) ? self::$actions[$code] : false;
  }

  public static function get_state($code, $force = false){
    if ( !isset(self::$states[$code]) || $force ){
      self::get_states(1);
      if ( !isset(self::$states[$code]) ){
        self::$states[$code] = false;
      }
    }
    return isset(self::$states[$code]) ? self::$states[$code] : false;
  }

  public static function get_role($code, $force = false){
    if ( !isset(self::$roles[$code]) || $force ){
      self::get_roles(1);
      if ( !isset(self::$roles[$code]) ){
        self::$roles[$code] = false;
      }
    }
    return isset(self::$roles[$code]) ? self::$roles[$code] : false;
  }

  public static function cat_correspondances(){
    if ( $opt = \bbn\appui\options::get_options() ){
      $cats = self::get_cats();
      $res = [];
      $opt->map(function ($a) use (&$res){
        array_push($res, [
          'value' => $a['id'],
          'text' => $a['text']
        ]);
        $a['is_parent'] = !empty($a['items']);
        if ( $a['is_parent'] ){
          $a['expanded'] = true;
        }
        return $a;
      }, $cats, 1);
      return $res;
    }
    return false;
  }

  public static function get_options(){
    if ( $opt = \bbn\appui\options::get_options() ){
      return [
        'states' => $opt->text_value_options(self::get_id_state()),
        'roles' => $opt->text_value_options(self::get_id_role()),
        'cats' => self::cat_correspondances()
      ];
    }
  }

  public function check(){
    return isset($this->user);
  }

  public function __construct(\bbn\db $db){
    $this->db = $db;
    if ( $user = \bbn\user\connection::get_user() ){
      $this->user = $user->get_name();
      $this->id_user = $user->get_id();
      $this->is_admin = $user->is_admin();
      $this->mgr = new \bbn\user\manager($user);
    }
    $this->columns = array_keys($this->db->get_columns('bbn_tasks'));
  }

  public function categories(){
    return self::get_cats();
  }

  public function actions(){
    return self::get_actions();
  }

  public function states(){
    return self::get_states();
  }

  public function roles(){
    return self::get_roles();
  }

  public function id_cat($code){
    return self::get_cat($code);
  }

  public function id_action($code){
    return self::get_action($code);
  }

  public function id_state($code){
    return self::get_state($code);
  }

  public function id_role($code){
    return self::get_role($code);
  }

  public function get_mine($parent = null, $order = 'priority', $dir = 'ASC', $limit = 50, $start = 0){
    return $this->get_list($parent, 'opened|ongoing|holding', $this->id_user, $order, $dir, $limit, $start);
  }

  public function translate_log(array $log){
    if ( ($opt = \bbn\appui\options::get_options()) && isset($log['action']) ){
      $type = explode('_', $opt->code($log['action']));
      $action = $opt->text($log['action']);
      $log['value'] = empty($log['value']) ? [] : json_decode($log['value']);
      if ( !empty($log['value']) ){
        $values = [];
        switch ( $type[0] ){
          case 'deadline':
            foreach ( $log['value'] as $v ){
              array_push($values, \bbn\date::format($v, 's'));
            }
            break;
          case 'title':
            $values = $log['value'];
            break;
          case 'comment':
            array_push($values, \bbn\str::cut($this->db->get_one("
              SELECT content
              FROM bbn_notes_versions
              WHERE id_note = ?
              ORDER BY version DESC
              LIMIT 1",
              $log['value'][0]), 80));
            break;
          case 'role':
            if ( ($user = \bbn\user\connection::get_user()) && isset($log['value'][0], $log['value'][1]) ){
              $values[0] = $user->get_name($this->mgr->get_user($log['value'][0]));
              $values[1] = $opt->text($log['value'][1]);
            }
            break;
          case 'priority':
            $values = $log['value'];
            break;
          default:
            foreach ( $log['value'] as $v ){
              array_push($values, $opt->text($v));
            }
        }
        if ( !empty($values) ){
          foreach ( $values as $i => $v ){
            $values[$i] = '<strong>'.$v.'</strong>';
          }
          array_unshift($values, $action);
          return call_user_func_array("sprintf", $values);
        }
      }
      return $action;
    }
  }

  public function get_log($id){
    $logs = $this->db->rselect_all('bbn_tasks_logs', [], ['id_task' => $id]);
    $res = [];
    foreach ( $logs as $log ){
      array_push($res, [
        'action' => $this->translate_log($log),
        'id_user' => $log['id_user'],
        'chrono' => $log['chrono']
      ]);
    }
    return $res;
  }

  public function get_all_logs($limit = 100, $start = 0){
    $logs = $this->db->rselect_all('bbn_tasks_logs', [], [], ['chrono' => 'DESC']);
    $res = [];
    foreach ( $logs as $log ){
      array_push($res, [
        'action' => $this->translate_log($log),
        'id_user' => $log['id_user'],
        'chrono' => $log['chrono']
      ]);
    }
    return $res;
  }

  public function get_list($parent = null, $status = 'opened|ongoing|holding', $id_user = false, $order = 'priority', $dir = 'ASC', $limit = 1000, $start = 0){
    $orders_ok = [
      'id' => 'bbn_tasks.id',
      'last' => 'last',
      'first' => 'first',
      'duration' => 'duration',
      'num_children' => 'num_children',
      'title' => 'title',
      'num_notes' => 'num_notes',
      'role' => 'role',
      'state' => 'state',
      'priority' => 'priority'
    ];
    if ( !isset($orders_ok[$order]) ||
      !\bbn\str::is_integer($limit, $start) ||
      (!is_null($parent) && !\bbn\str::is_integer($parent))
    ){
      return false;
    }
    $dir = strtolower($dir) === 'asc' ? 'ASC' : 'DESC';
    if ( !$id_user ){
      $id_user = $this->id_user;
    }
    $where = [];
    if ( !empty($status) ){
      $statuses = [];
      $tmp = explode("|", $status);
      foreach ( $tmp as $s ){
        if ( $t = $this->id_state($s) ){
          array_push($statuses, $t);
          array_push($where, "`bbn_tasks`.`state` = $t");
        }
      }
    }
    $where = count($where) ? implode( " OR ", $where) : '';
    $sql = "
    SELECT `role`, bbn_tasks.*,
    FROM_UNIXTIME(MIN(bbn_tasks_logs.chrono)) AS `first`,
    FROM_UNIXTIME(MAX(bbn_tasks_logs.chrono)) AS `last`,
    COUNT(children.id) AS num_children,
    COUNT(DISTINCT bbn_tasks_notes.id_note) AS num_notes,
    MAX(bbn_tasks_logs.chrono) - MIN(bbn_tasks_logs.chrono) AS duration
    FROM bbn_tasks_roles
      JOIN bbn_tasks
        ON bbn_tasks_roles.id_task = bbn_tasks.id
      JOIN bbn_tasks_logs
        ON bbn_tasks_logs.id_task = bbn_tasks_roles.id_task
      LEFT JOIN bbn_tasks_notes
        ON bbn_tasks_notes.id_task = bbn_tasks_roles.id_task
      LEFT JOIN bbn_tasks AS children
        ON bbn_tasks_roles.id_task = children.id_parent
    WHERE bbn_tasks_roles.id_user = ?".
      (empty($where) ? '' : " AND ($where)")."
    AND bbn_tasks.active = 1 
    AND bbn_tasks.id_alias IS NULL
    AND bbn_tasks.id_parent ".( is_null($parent) ? "IS NULL" : "= $parent" )." 
    GROUP BY bbn_tasks_roles.id_task
    LIMIT $start, $limit";

    $opt = \bbn\appui\options::get_options();
    $res = $this->db->get_rows($sql, $id_user);
    foreach ( $res as $i => $r ){
      $res[$i]['type'] = $opt->itext($r['type']);
      $res[$i]['state'] = $opt->itext($r['state']);
      $res[$i]['role'] = $opt->itext($r['role']);
      $res[$i]['hasChildren'] = $r['num_children'] ? true : false;
    }
    /*
    foreach ( $res as $i => $r ){
      $res[$i]['details'] = $this->info($r['id']);
    }
    */
    \bbn\x::sort_by($res, $order, $dir);
    return $res;
  }

  public function get_slist($search, $order = 'last', $dir = 'DESC', $limit = 1000, $start = 0){
    $orders_ok = [
      'id' => 'bbn_tasks.id',
      'last' => 'last',
      'first' => 'first',
      'duration' => 'duration',
      'num_children' => 'num_children',
      'title' => 'title',
      'num_notes' => 'num_notes',
      'role' => 'role',
      'state' => 'state',
      'priority' => 'priority'
    ];
    if ( !isset($orders_ok[$order]) || !\bbn\str::is_integer($limit, $start) ){
      return false;
    }
    $dir = strtolower($dir) === 'asc' ? 'ASC' : 'DESC';
    $sql = "
    SELECT bbn_tasks.*,
    FROM_UNIXTIME(MIN(bbn_tasks_logs.chrono)) AS `first`,
    FROM_UNIXTIME(MAX(bbn_tasks_logs.chrono)) AS `last`,
    COUNT(children.id) AS num_children,
    COUNT(DISTINCT bbn_tasks_notes.id_note) AS num_notes,
    MAX(bbn_tasks_logs.chrono) - MIN(bbn_tasks_logs.chrono) AS duration
    FROM bbn_tasks
      JOIN bbn_tasks_logs
        ON bbn_tasks_logs.id_task = bbn_tasks.id
      LEFT JOIN bbn_tasks_notes
        ON bbn_tasks_notes.id_task = bbn_tasks.id
      LEFT JOIN bbn_notes_versions
        ON bbn_notes_versions.id_note = bbn_tasks_notes.id_note
      LEFT JOIN bbn_tasks AS children
        ON children.id = bbn_tasks.id
    WHERE (bbn_tasks.title LIKE ?
    OR bbn_notes_versions.content LIKE ?)
    AND bbn_tasks.active = 1 
    GROUP BY bbn_tasks.id
    LIMIT $start, $limit";

    $opt = \bbn\appui\options::get_options();
    $res = $this->db->get_rows($sql, "%$search%");
    foreach ( $res as $i => $r ){
      $res[$i]['type'] = $opt->itext($r['type']);
      $res[$i]['state'] = $opt->itext($r['state']);
      $res[$i]['role'] = $opt->itext($r['role']);
      $res[$i]['hasChildren'] = $r['num_children'] ? true : false;
    }
    /*
    foreach ( $res as $i => $r ){
      $res[$i]['details'] = $this->info($r['id']);
    }
    */
    \bbn\x::sort_by($res, $order, $dir);
    return $res;
  }

  public function get_tree($id = null, $closed = false){
    $statuses = empty($closed) ? 'opened|ongoing|holding' : false;
    $res = [];
    $all = $this->get_list($id ?: null, $statuses, 5000);
    foreach ( $all as $a ){
      array_push($res, [
        'id' => $a['id'],
        'text' => $a['title'].' ('.\bbn\date::format($a['first']).'-'.\bbn\date::format($a['last']).')',
        'is_parent' => $a['num_children'] ? true : false
      ]);
    }
    return $res;
  }

  private function add_note($type, $value, $title){

  }

  public function add_link(){
    return $this->add_note();
  }

  public function info($id){
    if ( $info = $this->db->rselect('bbn_tasks', [], ['id' => $id]) ){

      $info['first'] = $this->db->select_one('bbn_tasks_logs', 'chrono', [
        'id_task' => $id,
        'action' => $this->id_action('insert')
      ], ['chrono' => 'ASC']);

      $info['last'] = $this->db->select_one('bbn_tasks_logs', 'chrono', [
        'id_task' => $id,
      ], ['chrono' => 'DESC']);
      $info['roles'] = $this->info_roles($id);
      $info['notes'] = $this->db->get_column_values('bbn_tasks_notes', 'id_note', ['id_task' => $id]);
      $info['children'] = $this->db->rselect_all('bbn_tasks', [], ['id_parent' => $id, 'active' => 1]);
      $info['aliases'] = $this->db->rselect_all('bbn_tasks', ['id', 'title'], ['id_alias' => $id, 'active' => 1]);
      $info['num_children'] = count($info['children']);
      if ( $info['num_children'] ){
        $info['has_children'] = 1;
        foreach ( $info['children'] as $i => $c ){
          $info['children'][$i]['num_children'] = $this->db->count('bbn_tasks', ['id_parent' => $c['id'], 'active' => 1]);
        }
      }
      else{
        $info['has_children'] = false;
      }
      return $info;
    }
  }

  public function search($st){
    $res = [];
    $res1 = $this->search_in_task($st);
    return $res1;
  }

  public function search_in_task($st){
    return $this->db->rselect_all('bbn_tasks', ['id', 'title', 'creation_date'], [['title',  'LIKE', '%'.$st.'%']]);
  }

  public function full_info($id){

  }

  public function info_roles($id){
    $r = [];
    if ( ($opt = \bbn\appui\options::get_options()) && self::get_id_role() ){
      $roles = $opt->full_options(self::$id_role);
      $all = $this->db->rselect_all('bbn_tasks_roles', [], ['id_task' => $id], 'role');
      $n = false;
      foreach ( $all as $a ){
        if ( $n !== $roles[$a['role']]['code'] ){
          $n = $roles[$a['role']]['code'];
          $r[$n] = [];
        }
        array_push($r[$n], $a['id_user']);
      }
    }
    return $r;
  }

  public function has_role($id_task, $id_user){
    if ( $opt = \bbn\appui\options::get_options() ){
      $r = $this->db->select_one('bbn_tasks_roles', 'role', ['id_task' => $id_task, 'id_user' => $id_user]);
      if ( $r ){
        return $opt->code($r);
      }
    }
    return false;
  }

  public function get_comments($id_task){

  }

  public function comment($id, $text){
    
  }

  public function add_log($id_task, $action, array $value = []){
    if ( $this->id_user ){
      return $this->db->insert('bbn_tasks_logs', [
        'id_task' => $id_task,
        'id_user' => $this->id_user,
        'action' => is_int($action) ? $action : $this->id_action($action),
        'value' => empty($value) ? '' : json_encode($value),
        'chrono' => microtime(true)
      ]);
    }
    return false;
  }

  public function exists($id_task){
    return $this->db->count('bbn_tasks', ['id' => $id_task]) ? true : false;
  }

  public function add_role($id_task, $role, $id_user = null){
    if ( $this->exists($id_task) ){
      if ( !\bbn\str::is_integer($role) ){
        if ( substr($role, -1) !== 's' ){
          $role .= 's';
        }
        $role = $this->id_role($role);
      }
      if ( \bbn\str::is_integer($role) && ($id_user || $this->id_user) ){
        if ( $this->db->insert('bbn_tasks_roles', [
          'id_task' => $id_task,
          'id_user' => $id_user ?: $this->id_user,
          'role' => $role
        ]) ){
          $this->add_log($id_task, 'role_insert', [$id_user ?: $this->id_user, $role]);
          return 1;
        }
      }
    }
    return 0;
  }

  public function remove_role($id_task, $id_user = null){
    if ( $this->exists($id_task) && ($id_user || $this->id_user) ){
      $role = $this->db->select_one('bbn_tasks_roles', 'role', [
        'id_task' => $id_task,
        'id_user' => $id_user ?: $this->id_user
      ]);
      if ( $this->db->delete('bbn_tasks_roles', [
        'id_task' => $id_task,
        'id_user' => $id_user ?: $this->id_user
      ]) ){
        $this->add_log($id_task, 'role_delete', [$id_user ?: $this->id_user, $role]);
        return 1;
      }
    }
    return 0;
  }

  public function insert($title, $type, $parent = null){
    $date = date('Y-m-d H:i:s');
    if ( $this->db->insert('bbn_tasks', [
      'title' => $title,
      'type' => $type,
      'priority' => 5,
      'id_parent' => $parent,
      'id_user' => $this->id_user ?: null,
      'state' => $this->id_state('opened'),
      'creation_date' => $date
    ]) ){
      $id = $this->db->last_id();
      $this->add_log($id, 'insert');
      $this->add_role($id, 'managers');
      /*
      $subject = "Nouveau bug posté par {$this->user}";
      $text = "<p>{$this->user} a posté un nouveau bug</p>".
        "<p><strong>$title</strong></p>".
        "<p>".nl2br($text)."</p>".
        "<p><em>Rendez-vous dans votre interface APST pour lui répondre</em></p>";
      $this->_email($id, $subject, $text);
      */
      return $id;
    }
  }

  public function update($id_task, $prop, $value){
    if ( $this->exists($id_task) ){
      $ok = false;
      if ( $prop === 'deadline' ){
        $prev = $this->db->select_one('bbn_tasks', 'deadline', ['id' => $id_task]);
        if ( !$prev && $value ){
          $this->add_log($id_task, 'deadline_insert', [$value]);
          $ok = 1;
        }
        else if ( $prev && !$value ){
          $this->add_log($id_task, 'deadline_delete', [$value]);
          $ok = 1;
        }
        if ( $prev && $value && ($prev !== $value) ){
          $this->add_log($id_task, 'deadline_update', [$prev, $value]);
          $ok = 1;
        }
      }
      else if ( $prev = $this->db->select_one('bbn_tasks', $prop, ['id' => $id_task]) ){
        $ok = 1;
        $this->add_log($id_task, $prop.'_update', [$prev, $value]);
      }
      if ( $ok ){
        return $this->db->update('bbn_tasks', [$prop => $value], ['id' => $id_task]);
      }
    }
    return false;
  }

  public function delete($id){
    if ( ($info = $this->info($id)) && $this->db->delete('bbn_tasks', ['id' => $id]) ){
      $subject = "Suppression du bug $info[title]";
      $text = "<p>{$this->user} a supprimé le bug<br><strong>$info[title]</strong></p>";
      $this->_email($id, $subject, $text);
      return $id;
    }
  }

  public function up($id){
    if ( $info = $this->info($id) ){
      return $this->update($id, $info['title'], $info['status'], $info['priority']-1, $info['deadline']);
    }
  }

  public function down($id){
    if ( $info = $this->info($id) ){
      return $this->update($id, $info['title'], $info['status'], $info['priority']+1, $info['deadline']);
    }
  }

  public function subscribe($id){
    return $this->db->insert('bbn_tasks_cc', ['id_user' => $this->id_user, 'id_task' => $id]);
  }

  public function unsubscribe($id){
    return $this->db->delete('bbn_tasks_cc', ['id_user' => $this->id_user, 'id_task' => $id]);
  }
}