<?php
const MAIN_PATH_IMI = "imi";

$section = $this->menuSection('Monitoring System', array(
    'icon'  => 'moon'
));
$section->add('IMI ', array(
    'url'       => MAIN_PATH_IMI,
    'priority'  => 100
));
$section->add('Down hosts', array(
    'url'       => MAIN_PATH_IMI.'/down',
    'priority'  => 101
));
$section->add('Warnings',array(
    'url'       => MAIN_PATH_IMI.'/warnings',
    'priority'  => 102
));
$section->add('Critical',array(
    'url'       => MAIN_PATH_IMI.'/critical',
    'priority'  => 103
));
$section->add('Critical and warnings',array(
    'url'       => MAIN_PATH_IMI.'/caw',
    'priority'  => 104
));
