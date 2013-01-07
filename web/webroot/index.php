<?php
require dirname(__DIR__) . '/bootstrap.php';

xhprof_enable(XHPROF_FLAGS_CPU | XHPROF_FLAGS_MEMORY);

$m = new Mongo();
$db = $m->xhprof;
$collection = $db->results;


//Let's get results from the database
$sort = array('meta.SERVER.REQUEST_TIME' => -1);
if (isset($_GET['sort'])) {
    if ($_GET['sort'] == 'wt') {
        $sort = array('profile.main().wt' => -1);
    } elseif ($_GET['sort'] == 'mu') {
        $sort = array('profile.main().mu' => -1);
    } elseif ($_GET['sort'] == 'cpu') {
        $sort = array('profile.main().cpu' => -1);
    }
}

$totalRows = $collection->find()->count();

$page = 1;
$totalPages = ceil($totalRows / DISPLAY_LIMIT);

if (isset($_GET['page'])) {
    $page = min(max($_GET['page'], 1), $totalPages);
}

$res = $collection->find()
    ->sort($sort)
    ->skip(($page - 1) * DISPLAY_LIMIT)
    ->limit(DISPLAY_LIMIT);

$template = load_template('runs/list.twig');
echo $template->render(array(
    'runs' => $res,
    'page' => $page,
    'sort' => $sort,
    'total_pages' => $totalPages,
));
flush();

//Store results


function _xhGetMeta()
{
    $meta = array(
        'url' => $_SERVER['REQUEST_URI'],
        'SERVER' => $_SERVER,
        'get' => $_GET,
        'env' => $_ENV,
        'simple_url' => simpleUrl($_SERVER['REQUEST_URI']),
    );
    return $meta;
}
$profile = xhprof_disable();
$data['meta'] = _xhGetMeta();
$data['profile'] = $profile;

$collection->insert($data);
$m = new Mongo();
$db = $m->xhprof;
$collection = $db->results;
