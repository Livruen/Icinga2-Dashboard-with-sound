<?php
/* Icinga Web 2 | (c) 2013-2015 Icinga Development Team | GPLv2+ */
/**
 * Created by PhpStorm.
 * Autor: Natasza Szczypien, Alexander Menk, Stephan Nachtsheim
 * Date: 09.02.16
 * Time: 11:42
 */


use Icinga\Data\Filter\Filter;
use Icinga\Module\Monitoring\Controller;
use Icinga\Module\Monitoring\Forms\Command\Object\AcknowledgeProblemCommandForm;
use Icinga\Module\Monitoring\Forms\Command\Object\AddCommentCommandForm;
use Icinga\Module\Monitoring\Forms\Command\Object\CheckNowCommandForm;
use Icinga\Module\Monitoring\Forms\Command\Object\ObjectsCommandForm;
use Icinga\Module\Monitoring\Forms\Command\Object\ProcessCheckResultCommandForm;
use Icinga\Module\Monitoring\Forms\Command\Object\RemoveAcknowledgementCommandForm;
use Icinga\Module\Monitoring\Forms\Command\Object\ScheduleServiceCheckCommandForm;
use Icinga\Module\Monitoring\Forms\Command\Object\ScheduleServiceDowntimeCommandForm;
use Icinga\Module\Monitoring\Forms\Command\Object\SendCustomNotificationCommandForm;
use Icinga\Module\Monitoring\Object\ServiceList;
use Icinga\Web\Url;
use Icinga\Web\Widget\Tabextension\DashboardAction;
use Icinga\Web\Widget\Tabextension\MenuAction;

class Imi_CawController extends Controller
{
    /**
     * @var ServiceList
     */
    protected $serviceList;


    const FIRST_TAB = 'imi';
    const SECOND_TAB = '/down';
    const THIRD_TAB = '/warnings';
    const FOURTH_TAB = '/critical';
    const FIFTH_TAB = '/caw';

    public function init()
    {
        $serviceList = new ServiceList($this->backend);
        $this->applyRestriction('monitoring/filter/objects', $serviceList);
        $serviceList->addFilter(Filter::fromQueryString(
            (string)$this->params->without(array('service_problem', 'service_handled', 'view'))
        ));
        $this->serviceList = $serviceList;
        $this->view->baseFilter = $this->serviceList->getFilter();
        $this->view->listAllLink = Url::fromRequest()->setPath('monitoring/list/services');
    }


    public function indexAction()
    {
        $this->getTabs()->activate(self::FIFTH_TAB);

        $checkNowForm = new CheckNowCommandForm();
        $checkNowForm
            ->setObjects($this->serviceList)
            ->handleRequest();
        $this->view->checkNowForm = $checkNowForm;
        $this->serviceList->setColumns(array(
            'host_display_name',
            'host_handled',
            'host_name',
            'host_problem',
            'host_state',
            'service_acknowledged',
            'service_active_checks_enabled',
            'service_description',
            'service_display_name',
            'service_handled',
            'service_in_downtime',
            'service_is_flapping',
            'service_last_state_change',
            'service_notifications_enabled',
            'service_passive_checks_enabled',
            'service_problem',
            'service_state'
        ));


        $this->setAutorefreshInterval(15);
        $problems = $this->serviceList->getProblemObjects();
        $services = array();
        foreach($problems as $problem){
            array_push($services, $problem);
        }
        sort($services);
        $currentHostName = "";
        $outputArray = array();
        $currentGroup = null;

        foreach ($services as $service) {
            $hostname = $service->host_name;
            if ($currentHostName != $hostname) { // unterschiedliche hosts
                if ($currentGroup != null) { // es gibt eine gruppe
                   // array_push($outputArray, $currentGroup);
                    $outputArray[] = $currentGroup;
                }
                    $currentGroup = ['criticals' => 0, 'warnings' => 0, 'name' => $hostname];
                    $currentHostName = $hostname;
            }
                if ($this->isCritical($service)) {
                    $currentGroup['criticals']++;
                }
                if ($this->inWarningState($service)) {
                    $currentGroup['warnings']++;
            }
        }
        array_push($outputArray, $currentGroup);
        $this->viewCaW($outputArray);
    }

    /**
     * @param $services
     * @return bool
     */
    private function isCritical($services)
    {
        return $services->service_state == \Icinga\Module\Monitoring\Object\Service::STATE_CRITICAL;
    }

    /**
     * @param $services
     * @return bool
     */
    private function inWarningState($services)
    {
        return $services->service_state == \Icinga\Module\Monitoring\Object\Service::STATE_WARNING;
    }

    /**
     * @param $outputArray
     */
    private function viewCaW($outputArray)
    {
        $this->view->objects = $outputArray;
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
