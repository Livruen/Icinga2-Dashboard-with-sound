
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

class Imi_CriticalController extends Icinga\Module\Monitoring\Controller
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
            (string) $this->params->without(array('service_problem', 'service_handled', 'view'))
        ));
        $this->serviceList = $serviceList;
        $this->view->baseFilter = $this->serviceList->getFilter();
        $this->view->listAllLink = Url::fromRequest()->setPath('monitoring/list/services');
    }

    public function indexAction()
    {
        $this->getTabs()->activate(self::FOURTH_TAB);
        $this->setAutorefreshInterval(15);
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

        $criticalServices = array();
        $problemObjects = $this->serviceList->getProblemObjects();

        foreach($problemObjects as $service){
            if($this->inCriticalState($service)){
                array_push($criticalServices, $service); // fill the list with critical states
            }
        }


        if($this->newCriticalService($criticalServices))
        {
            $this->sendSignal();
            $this->display($criticalServices);
        }
        else if($this->noCriticals($criticalServices))
        {
            $this->view->noCriticals = true; // creates an echo in index.phtml
        } else
        {
            $this->display($criticalServices);
        }



       /* $socket = new Energenie();
        $socket->Login();
        $socket->PowerOn(1);*/

    }

    /**
     * @param $services
     * @return bool
     */
    private function inCriticalState($services)
    {
        return \Icinga\Module\Monitoring\Object\Service::STATE_CRITICAL == $services->service_state;
    }

    /**
     * @return bool
     */
    private function noCriticals($criticalList)
    {
        return empty($criticalList);

    }

    /**
     * Checks if the critical list has a new service that is not acknowledged
     * @return bool
     */
    private function newCriticalService($criticalList)
    {
        foreach($criticalList as $service){
            if(!$service->service_acknowledged) {
                return true;
            }
        }
        return false;
    }

    /**
     * Activates a sound in index.phtml
     */
    private function sendSignal() {
        $this->view->criticalSignal = true;
    }

    /**
     * Send a prepared Array to index.phtml
     * @param $criticalServices
     */
    private function display($criticalServices)
    {
        $this->view->criticalsSummary = count($criticalServices);
        $preparedArray = $this->prepareForView($criticalServices);
        $this->view->objects = $preparedArray;
    }


    /**
     * @param $preparedArray ['hostname' , 'services' => array( with critical services ) ]
     * Prepares an array for the view
     */
    private function prepareForView($criticalServices)
    {
        $preparedArray = array();
        sort($criticalServices);
        $currentHostName = "";
        $currentGroup = null;
        foreach($criticalServices as $service){

            $hostname = $service->host_name;
            if($currentHostName != $hostname){
                if($currentGroup != null) {
                    array_push($preparedArray , $currentGroup);
                }
                $currentGroup = ['hostname' => $hostname, 'services' => array()];
                $currentHostName = $hostname;
            }
                if($this->inCriticalState($service)){
                    array_push($currentGroup['services'], $service);
                }
        }
        array_push($preparedArray, $currentGroup);
        return $preparedArray;
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
