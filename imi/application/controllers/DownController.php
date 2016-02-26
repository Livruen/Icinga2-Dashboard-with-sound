
<?php
/* Icinga Web 2 | (c) 2013-2015 Icinga Development Team | GPLv2+ */
/**
 * Created by PhpStorm.
 * Autor: Natasza Szczypien, Alexander Menk, Stephan Nachtsheim
 * Date: 09.02.16
 * Time: 11:42
 */

use Icinga\Data\Filter\Filter;
use Icinga\Data\Filter\FilterEqual;
use Icinga\Module\Monitoring\Controller;
use Icinga\Module\Monitoring\Forms\Command\Object\AcknowledgeProblemCommandForm;
use Icinga\Module\Monitoring\Forms\Command\Object\AddCommentCommandForm;
use Icinga\Module\Monitoring\Forms\Command\Object\CheckNowCommandForm;
use Icinga\Module\Monitoring\Forms\Command\Object\ObjectsCommandForm;
use Icinga\Module\Monitoring\Forms\Command\Object\ProcessCheckResultCommandForm;
use Icinga\Module\Monitoring\Forms\Command\Object\RemoveAcknowledgementCommandForm;
use Icinga\Module\Monitoring\Forms\Command\Object\ScheduleHostCheckCommandForm;
use Icinga\Module\Monitoring\Forms\Command\Object\ScheduleHostDowntimeCommandForm;
use Icinga\Module\Monitoring\Forms\Command\Object\SendCustomNotificationCommandForm;
use Icinga\Module\Monitoring\Object\Host;
use Icinga\Module\Monitoring\Object\HostList;
use Icinga\Web\Url;
use Icinga\Web\Widget\Tabextension\DashboardAction;
use Icinga\Web\Widget\Tabextension\MenuAction;

class Imi_DownController extends Icinga\Module\Monitoring\Controller
{
    protected $hostList;
    const FIRST_TAB = 'imi';
    const SECOND_TAB = '/down';
    const THIRD_TAB = '/warnings';
    const FOURTH_TAB = '/critical';
    const FIFTH_TAB = '/caw';

    public function init()
    {
        $hostList = new HostList($this->backend);
        $this->applyRestriction('monitoring/filter/objects', $hostList);
        $hostList->addFilter(Filter::fromQueryString((string) $this->params));
        $this->hostList = $hostList;
        $this->view->baseFilter = $this->hostList->getFilter();
        $this->view->listAllLink = Url::fromRequest()->setPath('monitoring/list/hosts');
    }

    protected function handleCommandForm(ObjectsCommandForm $form)
    {
        // Erstellt ein Array
        $this->hostList->setColumns(array(
            'host_acknowledged',
            'host_active_checks_enabled',
            'host_display_name',
            'host_handled',
            'host_in_downtime',
            'host_is_flapping',
            'host_last_state_change',
            'host_name',
            'host_notifications_enabled',
            'host_passive_checks_enabled',
            'host_problem',
            'host_state'
        ));

        $form
            ->setObjects($this->hostList)
            ->setRedirectUrl(Url::fromPath('imi/down/index')->setParams($this->params))
            ->handleRequest();

        $this->view->form = $form;
        $this->view->objects = $this->hostList;
        $this->view->stats = $this->hostList->getStateSummary();
        return $form;
    }

    public function indexAction()
    {
        $this->getTabs()->activate(self::SECOND_TAB);

        $this->setAutorefreshInterval(15);
        $checkNowForm = new CheckNowCommandForm();
        $checkNowForm
            ->setObjects($this->hostList)
            ->handleRequest();
        $this->view->checkNowForm = $checkNowForm;
        /* Use hostListObject->__get('') for getting the params  */
        $this->hostList->setColumns(array(
            'host_acknowledged',
            'host_active_checks_enabled',
            'host_display_name',
            'host_handled',
            'host_in_downtime',
            'host_is_flapping',
            'host_last_state_change',
            'host_name',
            'host_notifications_enabled',
            'host_passive_checks_enabled',
            'host_problem',
            'host_state'
        ));

        $problemObjects = $this->hostList->getProblemObjects();
        $unacknowledgedObjects = $this->hostList->getUnacknowledgedObjects(); 

        if($this->newDownObjects($unacknowledgedObjects))
        {
            $this->sendSignal();
            $this->display($problemObjects);
        }
        else if($this->allHostsUp($problemObjects))
        {
            $this->view->hostsUp = true;
        }
        else
        {
            $this->display($problemObjects);
        }
    }

    /**
     * Activates a sound in index.phtml
     */
    private function sendSignal()
    {
         $this->view->downSignal = true;
    }

    /**
     * @return bool
     * If the List has unacklowledged problems send a signal "Host is down"
     */
    private function newDownObjects($list)
    {
        return !empty($list);
    }

    /**
     * @return bool
     * If all Hosts are up there are no problem objects
     */
    private function allHostsUp()
    {
        return  empty($this->tempList);
    }

    /**
     * Sends information to index.phtml
     */
    private function display($list)
    {
        $this->view->downNumber = count($list);
        $this->view->downHostList = $list;
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
