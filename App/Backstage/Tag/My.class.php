<?php

namespace Backstage\Tag;

use Think\Template\TagLib;

class My extends TagLib {
    // 定义标签
    protected $tags = array(
        'jquery' => array('attr' => '', 'close' => 0),
        'oreocss' => array('attr' => 'icheck', 'close' => 0),
        'oreojs' => array('attr' => 'icheck', 'close' => 0),
        'formcss' => array('attr' => 'icheck', 'close' => 0),
        'formjs' => array('attr' => 'icheck', 'close' => 0),
    );

    /**
     * jquery
     */
    public function _jquery() {
        $str = "<script src='__STATIC__/js/jquery-1.10.2.min.js'></script>
";

        return $str;
    }

    // 集合css
    public function _oreocss($tag, $content) {
        $link = '
<link rel="stylesheet" href="__ASSETS__/plugins/bootstrap/css/bootstrap.min.css">
<link rel="stylesheet" href="__ASSETS__/plugins/bootstrap-select/css/bootstrap-select.css"/>
<link rel="stylesheet" href="__ASSETS__/plugins/jquery-datatable/dataTables.bootstrap4.min.css">
<link rel="stylesheet" href="__ASSETS__/plugins/sweetalert/sweetalert.css">
<link rel="stylesheet" href="__ASSETS__/css/main.css">
<link rel="stylesheet" href="__ASSETS__/css/color_skins.css">
<link rel="stylesheet" href="__ASSETS__/css/custom.css">
';
        unset($tag);
        unset($content);

        return $link;
    }

    // 集合js
    public function _oreojs($tag, $content) {
        $link = '
<script src="__ASSETS__/bundles/libscripts.bundle.js"></script>
<script src="__ASSETS__/bundles/vendorscripts.bundle.js"></script>
<script src="__ASSETS__/bundles/mainscripts.bundle.js"></script>
<script src="__ASSETS__/plugins/bootstrap-notify/bootstrap-notify.js"></script>
<script src="__ASSETS__/js/pages/ui/notifications.js"></script>
<script src="__ASSETS__/plugins/sweetalert/sweetalert.min.js"></script>
<script src="__ASSETS__/bundles/datatablescripts.bundle.js"></script>
';
        unset($tag);
        unset($content);

        return $link;
    }
    
    // 表单css
    public function _formcss($tag, $content) {
        $link = '
<link rel="stylesheet" href="__ASSETS__/plugins/jquery-datetimepicker/jquery.datetimepicker.min.css"/>
';
        unset($tag);
        unset($content);
        
        return $link;
    }

    // 表单js
    public function _formjs($tag, $content) {
        $link = '
<script src="__ASSETS__/plugins/momentjs/moment.js"></script>
<script src="__ASSETS__/plugins/momentjs/moment_zh.js"></script>
<script src="__ASSETS__/plugins/sweetalert/sweetalert.min.js"></script>
<script src="__ASSETS__/plugins/jquery-datetimepicker/jquery.datetimepicker.full.min.js"></script>
<script src="__ASSETS__/plugins/bootstrap-material-datetimepicker/js/bootstrap-material-datetimepicker.js"></script>
<script src="__ASSETS__/js/pages/forms/basic-form-elements.js"></script>
';
        unset($tag);
        unset($content);

        return $link;
    }

}

