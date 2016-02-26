
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


/*
        class Energenie
        {


            const HOST = "10.4.2.98";
            const PASSWORD = "1";
            const ON = 1;
            const OFF = 0;
            const TIMEOUT = 1000;
            const AKTIV = 102;
            const INVALID = 203;

                public function Create()
                {
                    parent::Create();
                    $this->RegisterPropertyString("" . self::HOST . "", "");
                    $this->RegisterPropertyString("" . self::PASSWORD . "", "");
                    $this->RegisterPropertyInteger("UpdateInterval", 15);
                }


            public function ApplyChanges()
        {

            //parent::ApplyChanges();
            //IP Prüfen
            //$ip = $this->ReadPropertyString('Host');
            //$passwort = $this->ReadPropertyString('Passwort');
            if (!filter_var(self::HOST, FILTER_VALIDATE_IP) === false && self::PASSWORD !== "")
            {
                $this->SetStatus(self::AKTIV); //IP Adresse ist gültig -> aktiv
            }
            elseif (self::PASSWORD == "")
            {
                $this->SetStatus(self::INVALID); //Passwort Feld ist leer
            }
            elseif (filter_var(self::HOST, FILTER_VALIDATE_IP) === false)
            {
                $this->SetStatus(self::INVALID); //IP Adresse ist ungültig
            }

             $this->RegisterTimer('INTERVAL', $this->ReadPropertyInteger('UpdateInterval'), 'EGPMSLAN_getStatus($id)');

             $state1 = $this->RegisterVariableBoolean("STATE1", "Status Dose 1", "~Switch", 1);
             $this->EnableAction("STATE1");
             $state1 = $this->RegisterVariableBoolean("STATE2", "Status Dose 2", "~Switch", 1);
             $this->EnableAction("STATE2");
             $state1 = $this->RegisterVariableBoolean("STATE3", "Status Dose 3", "~Switch", 1);
             $this->EnableAction("STATE3");
             $state1 = $this->RegisterVariableBoolean("STATE4", "Status Dose 4", "~Switch", 1);
             $this->EnableAction("STATE4");


        }
            protected function RegisterTimer($ident, $interval, $script) {
            $id = @IPS_GetObjectIDByIdent($ident, $this->InstanceID);
            if ($id && IPS_GetEvent($id)['EventType'] <> 1) {
                IPS_DeleteEvent($id);
                $id = 0;
            }
            if (!$id) {
                $id = IPS_CreateEvent(1);
                IPS_SetParent($id, $this->InstanceID);
                IPS_SetIdent($id, $ident);
            }
            IPS_SetName($id, $ident);
            IPS_SetHidden($id, true);
            IPS_SetEventScript($id, "\$id = \$_IPS['TARGET'];\n$script;");
            if (!IPS_EventExists($id)) throw new Exception("Ident with name $ident is used for wrong object type");
            if (!($interval > 0)) {
                IPS_SetEventCyclic($id, 0, 0, 0, 0, 1, 1);
                IPS_SetEventActive($id, false);
            } else {
                IPS_SetEventCyclic($id, 0, 0, 0, 0, 1, $interval);
                IPS_SetEventActive($id, true);
            }
        }
            public function RequestAction($ident, $value)
        {
            switch ($ident) {
                case 'STATE1':
                    if ($value == true) {
                        $this->PowerOn(1);
                    } else {
                        $this->PowerOff(1);
                    }
                    break;
                case 'STATE2':
                    if ($value == true) {
                        $this->PowerOn(2);
                    } else {
                        $this->PowerOff(2);
                    }
                    break;
                case 'STATE3':
                    if ($value == true) {
                        $this->PowerOn(3);
                    } else {
                        $this->PowerOff(3);
                    }
                    break;
                case 'STATE4':
                    if ($value == true) {
                        $this->PowerOn(4);
                    } else {
                        $this->PowerOff(4);
                    }
                    break;
            }
        }

            protected function Logout()
        {

            $html = $this->postRequest('http://'.self::HOST.'/login.html', array('pw' => ''));
            if (strstr($html, "EnerGenie Web:"))
                $result=TRUE;
            else
                $result=FALSE;

            return $result;
        }

            public function Login()
        {
            $html = $this->postRequest('http://'.self::HOST.'/login.html', array('pw' => self::PASSWORD));
            if ($html=="" OR strstr($html, "EnerGenie Web:"))
                $result=FALSE;
            else
                $result=TRUE;

            return $result;
        }


            protected function lastChange()
            {
                $lastchange1 = IPS_GetVariable($this->GetIDForIdent('STATE1'))["VariableChanged"];
                $lastchange2 = IPS_GetVariable($this->GetIDForIdent('STATE2'))["VariableChanged"];
                $lastchange3 = IPS_GetVariable($this->GetIDForIdent('STATE3'))["VariableChanged"];
                $lastchange4 = IPS_GetVariable($this->GetIDForIdent('STATE4'))["VariableChanged"];
                $currenttime = time();
                $lastswitch = $currenttime - $lastchange1;
                return $lastswitch;
            }

            //Get State
            public function getStatus()
            {
                if ($this->Login())
                {
                 //   $ip = $this->ReadPropertyString('Host');
                    $html = $this->getRequest('http://'.HOST.'/energenie.html', array());
                    preg_match_all('/var sockstates \= \[([0-1],[0,1],[0,1],[0,1])\]/', $html, $matches);
                    if(!isset($matches[1][0])) { return false; }
                    $states = explode(',', $matches[1][0]);
                    $this->Logout();

                    return array(1=>$states[0], 2=>$states[1], 3=>$states[2], 4=>$states[3]);
                    if ($states[0] == 0)
                    {
                        SetValueBoolean($this->GetIDForIdent('STATE1'), true);
                    }
                    elseif ($states[0] == 1)
                    {
                        SetValueBoolean($this->GetIDForIdent('STATE1'), false);
                    }
                    elseif ($states[1] == 0)
                    {
                        SetValueBoolean($this->GetIDForIdent('STATE2'), true);
                    }
                    elseif ($states[1] == 1)
                    {
                        SetValueBoolean($this->GetIDForIdent('STATE2'), false);
                    }
                    elseif ($states[2] == 0)
                    {
                        SetValueBoolean($this->GetIDForIdent('STATE3'), true);
                    }
                    elseif ($states[2] == 1)
                    {
                        SetValueBoolean($this->GetIDForIdent('STATE3'), false);
                    }
                    elseif ($states[3] == 0)
                    {
                        SetValueBoolean($this->GetIDForIdent('STATE4'), true);
                    }
                    elseif ($states[3] == 1)
                    {
                        SetValueBoolean($this->GetIDForIdent('STATE4'), false);
                    }
                }
                else
                    return false;
            }


            protected function doSwitch($switches)
        {
            if ($this->Login())
            {
                foreach($switches as $port => $state)
                {
                    $ports = array(1 => '', 2 => '', 3 => '', 4 => '');
                    $ports[$port] = $state;
                    $params = array();
                    foreach($ports as $port => $state)
                    {
                        if(in_array($state, array(self::ON, self::OFF)))
                        {
                            $params['cte'.$port] = $state;
                        }
                    }
                    $this->postRequest('http://'.$this->ReadPropertyString('Host'), $params);
                }
                $this->Logout();
            }
        }

            protected function postRequest($url, $fields)
        {
            $fields_string_array = array();
            foreach((array)$fields as $key=>$value)
            {
                $fields_string_array[] = $key.'='.$value;
            }
            $fields_string = join('&', $fields_string_array);
            //open connection
            $ch = curl_init();

            // configure
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT_MS, self::TIMEOUT);
            //set the url, number of POST vars, POST data
            curl_setopt($ch, CURLOPT_URL, $url);
            //curl_setopt($ch, CURLOPT_USERAGENT, "IPSymcon");
            curl_setopt($ch, CURLOPT_POST, count($fields));
            curl_setopt($ch, CURLOPT_POSTFIELDS, $fields_string);

            //execute post
            $result = curl_exec($ch);
            //$status = curl_getinfo($ch, CURLINFO_HTTP_CODE);

            //close connection
            curl_close($ch);

            return $result;

        }

            protected function getRequest($url, $fields)
        {
            $fields_string_array = array();
            foreach((array)$fields as $key=>$value)
            {
                $fields_string_array[] = $key.'='.$value;
            }
            $fields_string = join('&', $fields_string_array);
            //open connection
            $ch = curl_init();

            // configure
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT_MS, self::TIMEOUT);
            //set the url, number of POST vars, POST data
            curl_setopt($ch, CURLOPT_URL, $url . ($fields_string != '' ? '?' . $fields_string : ''));

            //execute post
            $result = curl_exec($ch);

            //close connection
            curl_close($ch);

            // provide html
            return $result;
        }

            public function PowerOn($slot)
        {
            //$switchstate = $this->getStatus();
            switch ($slot)
            {
                case 1:
                    $switchslots = array(
                        1 => self::ON
                    );
                    break;

                case 2:
                    $switchslots = array(
                        2 => self::ON
                    );
                    break;

                case 3:
                    $switchslots = array(
                        3 => self::ON
                    );
                    break;

                case 4:
                    $switchslots = array(
                        4 => self::ON
                    );
                    break;
            }

            $slotIdent = "STATE".$slot;
            // Puffer damit nicht sofort hintereinander geschaltet wird
            $lastswitch = $this->lastChange();
            if ($lastswitch <10)
            {
                $waitswitch = (10 - $lastswitch) * 100;
                IPS_Sleep($waitswitch);
                SetValueBoolean($this->GetIDForIdent($slotIdent), true);
                return $this->doSwitch($switchslots);
            }
            else
            {
                SetValueBoolean($this->GetIDForIdent($slotIdent), true);
                return $this->doSwitch($switchslots);
            }
        }
            public function PowerOff($slot)
        {
            //$switchstate = $this->getStatus();
            switch ($slot)
            {
                case 1:
                    $switchslots = array(
                        1 => self::OFF
                    );
                    break;

                case 2:
                    $switchslots = array(
                        2 => self::OFF
                    );
                    break;

                case 3:
                    $switchslots = array(
                        3 => self::OFF
                    );
                    break;

                case 4:
                    $switchslots = array(
                        4 => self::OFF
                    );
                    break;
            }

            $slotIdent = "STATE".$slot;
            // Puffer damit nicht sofort hintereinander geschaltet wird
            $lastswitch = $this->lastChange();
            if ($lastswitch <10)
            {
                $waitswitch = (10 - $lastswitch) * 100;
                IPS_Sleep($waitswitch);
                SetValueBoolean($this->GetIDForIdent($slotIdent), false);
                return $this->doSwitch($switchslots);
            }
            else
            {
                SetValueBoolean($this->GetIDForIdent($slotIdent), false);
                return $this->doSwitch($switchslots);
            }

        }
    }
*/