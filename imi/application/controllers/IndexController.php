<?php
/* Icinga Web 2 | (c) 2013-2015 Icinga Development Team | GPLv2+ */
/**
 * Created by PhpStorm.
 * Autor: Natasza Szczypien, Alexander Menk, Stephan Nachtsheim
 * Date: 09.02.16
 * Time: 11:42
 */

use Icinga\Web\Controller\ModuleActionController;


class Imi_IndexController extends ModuleActionController
{
    const FIRST_TAB = 'imi';
    const SECOND_TAB = '/down';
    const THIRD_TAB = '/warnings';
    const FOURTH_TAB = '/critical';
    const FIFTH_TAB = '/caw';

    public function indexAction()
    {
        $this->getTabs()->activate('Imi');
    }

    public function getTabs()
    {
        $tabs = parent::getTabs();
        $tabs->add(
            'Imi',
            array(
                'title' => 'Imi',
                'url'   => 'imi'
            )
        );
        $tabs->add(
            self::SECOND_TAB,
            array(
                'title' =>  self::FIRST_TAB.self::SECOND_TAB,
                'url'   => self::FIRST_TAB.self::SECOND_TAB
            )
        );
        $tabs->add(
            self::THIRD_TAB,
            array(
                'title' =>  self::FIRST_TAB.self::THIRD_TAB,
                'url'   => self::FIRST_TAB.self::THIRD_TAB
            )
        );
        $tabs->add(
            self::FOURTH_TAB,
            array(
                'title' =>  self::FIRST_TAB.self::FOURTH_TAB,
                'url'   => self::FIRST_TAB.self::FOURTH_TAB
            )
        );
        $tabs->add(
            self::FIFTH_TAB,
            array(
                'title' =>  self::FIRST_TAB.self::FIFTH_TAB,
                'url'   => self::FIRST_TAB.self::FIFTH_TAB
            )
        );


        return $tabs;
    }
}
