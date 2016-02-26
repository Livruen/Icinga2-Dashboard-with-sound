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

class Imi_WarningsController extends Controller
{
    /**
     * @var ServiceList
     */
    protected $serviceList;

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
        $this->getTabs()->add(
            'show',
            array(
                'label' => $this->translate('Services') . sprintf(' (%d)', count($this->serviceList)),
                'title' => sprintf(
                    $this->translate('Show summarized information for %u services'),
                    count($this->serviceList)
                ),
                'url'   => Url::fromRequest()
            )
        )->extend(new DashboardAction())->extend(new MenuAction())->activate('show');
    }

    public function indexAction()
    {

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

        $warningList = array();
        $problemObjects = $this->serviceList->getProblemObjects();

        foreach($problemObjects as $service){
            if($this->inWarningState($service)){
                array_push($warningList, $service); // fill the list with warning states
            }
        }

        if($this->newWarningObjects($warningList))
        {
            $this->sendSignal();
            $this->display($warningList);
        }
        else if($this->noWarnings($warningList))
        {
            $this->view->noWarnings = true; // creates an echo in index.phtml
        } else {
            $this->display($warningList);
        }

    }

    /**
     * @param $services
     * @return bool
     */
    private function inWarningState($services)
    {
        return $services->service_state  == \Icinga\Module\Monitoring\Object\Service::STATE_WARNING;
    }

    /**
     * @return bool
     */
    private function noWarnings( $warningList)
    {
        return empty($warningList);

    }

    /**
     * Checks if the warning list has a new service that is not acknowledged
     * @return bool
     */
    private function newWarningObjects($warningList)
    {
        foreach($warningList as $service){
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
        $this->view->warningSignal = true;
    }

    /**
     * display in /views/warnings/index.phtml
     * @param $warningList
     */
    private function display($warningList)
    {
        $this->view->warningsSummary = count($warningList);
        $preparedArray = $this->prepareForView($warningList);
        $this->view->objects = $preparedArray;
    }
    /**
     * @param $preparedArray ['hostname' , 'services' => array('String') ]
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
            if($this->inWarningState($service)){
                array_push($currentGroup['services'], $service);
            }
        }
        array_push($preparedArray, $currentGroup);
        return $preparedArray;
    }
}
