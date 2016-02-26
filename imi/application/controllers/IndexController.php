<?php
/* Icinga Web 2 | (c) 2013-2015 Icinga Development Team | GPLv2+ */
/**
 * Created by PhpStorm.
 * Autor: Natasza Szczypien, Alexander Menk, Stephan Nachtsheim
 * Date: 09.02.16
 * Time: 11:42
 */
define('MAIN_PATH_IMI', 'imi');

define('FIRST_TAB_NAME', 'imi');
define('FIRST_TAB_PATH', 'imi');


define('SECOND_TAB_NAME', 'DOWN_Servers');
define('SECOND_TAB_PATH', MAIN_PATH_IMI.'/down');
define('THIRD_TAB_NAME','WARNINGS');
define('THIRD_TAB_PATH', MAIN_PATH_IMI.'/warnings');

define('FOURTH_TAB_NAME','CRITICAL');
define('FOURTH_TAB_PATH', MAIN_PATH_IMI.'/critical');

define('FIFTH_TAB_NAME','CRITICAL_AND_WARNINGS');
define('FIFTH_TAB_PATH', MAIN_PATH_IMI.'/caw');
use Icinga\Web\Controller\ModuleActionController;


class Imi_IndexController extends ModuleActionController
{

    public function indexAction()
    {
        $this->getTabs()->activate(FIRST_TAB_NAME);
    }

    public function getTabs()
    {
        $tabs = parent::getTabs();
        $tabs->add(
            FIRST_TAB_NAME,
            array(
                'title' => FIRST_TAB_NAME,
                'url'   => FIRST_TAB_PATH
            )
        );
       $tabs->add(
            SECOND_TAB_NAME,
            array(
                'title' =>  SECOND_TAB_NAME,
                'url'   => SECOND_TAB_PATH
            )
        );
        $tabs->add(
            THIRD_TAB_NAME,
            array(
                'title' =>  THIRD_TAB_NAME,
                'url'   => THIRD_TAB_PATH
            )
        );
        $tabs->add(
            FOURTH_TAB_NAME,
            array(
                'title' =>  FOURTH_TAB_NAME,
                'url'   => FOURTH_TAB_PATH
            )
        );
        $tabs->add(
            FIFTH_TAB_NAME,
            array(
                'title' =>  FIFTH_TAB_NAME,
                'url'   => FIFTH_TAB_PATH
            )
        );




        return $tabs;
    }
}
