<?php
$original=$argv[1] ?? dirname(__DIR__, 2);
$source=dirname(__DIR__, 2);
require $original.'/tests/Fixtures/bootstrap.php';
view()->share('errors', new Illuminate\Support\ViewErrorBag());
$compiler=app('blade.compiler');
$compiler->anonymousComponentPath($source.'/platform/core/base/resources/views/components/license','qa');
foreach([null,false,true] as $permission){
 $view=$permission===null?'<x-qa::form />':'<x-qa::form :manage-license="$permission" />';
 $html=Illuminate\Support\Facades\Blade::render($view,['permission'=>$permission]);
 $dom=new DOMDocument();@$dom->loadHTML('<?xml encoding="UTF-8">'.$html);
 foreach(['buyer','purchase_code','licenseRulesAgreement'] as $id){
  $node=$dom->getElementById($id);if(!$node)throw new RuntimeException('Missing '.$id);
  if($node->hasAttribute('disabled')!==($permission!==true))throw new RuntimeException('Permission not retained on '.$id);
 }
 if(str_contains($html,'Your license is fully activated and verified.'))throw new RuntimeException('False activation claim remains');
 echo 'PASS component permission='.var_export($permission,true).PHP_EOL;
}
