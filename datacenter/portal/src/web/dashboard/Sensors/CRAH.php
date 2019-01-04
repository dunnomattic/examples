<?php

namespace Dashboard\Sensors;

class CRAH {

    public $deviceName;
    public $chillerValve;
    public $returnAirHumidity;
    public $supplyAirHumidity;
    public $returnAirTemperature;
    public $supplyAirTemperature;
    protected $unitStatus;
    public $fanStatus;
    public $enteringWaterTemperature;
    public $leavingWaterTemperature;
    public $DAT;
    protected $alertRAT;
    protected $alertRAH;
    protected $alertSAT;
    protected $alertCHWV;

    public function __construct($deviceName = '') {
        $this->deviceName = $deviceName;
    }

    /**
     * @return float
     */
    public function getChillerValve()
    {
        return $this->chillerValve;
    }

    /**
     * @param float $chillerValve
     */
    public function setChillerValve($chillerValve)
    {
        if($chillerValve <= 100)
            $this->chillerValve = $chillerValve;
        else
            $this->chillerValve = $chillerValve/10;
    }

    /**
     * @return float
     */
    public function getReturnAirHumidity()
    {
        return $this->returnAirHumidity;
    }

    /**
     * @param float $returnAirHumidity
     */
    public function setReturnAirHumidity($returnAirHumidity)
    {
        if($returnAirHumidity <= 100)
            $this->returnAirHumidity = $returnAirHumidity;
        else
            $this->returnAirHumidity = $returnAirHumidity/10;
    }

    /**
     * @return float
     */
    public function getSupplyAirHumidity()
    {
        return $this->supplyAirHumidity;
    }

    /**
     * @param float $supplyAirHumidity
     */
    public function setSupplyAirHumidity($supplyAirHumidity)
    {
        $this->supplyAirHumidity = $supplyAirHumidity;
    }

    /**
     * @return float
     */
    public function getReturnAirTemperature()
    {
        return $this->returnAirTemperature;
    }

    /**
     * @param float $returnAirTemperature
     */
    public function setReturnAirTemperature($returnAirTemperature)
    {
        $this->returnAirTemperature = $returnAirTemperature;
    }

    /**
     * @return float
     */
    public function getSupplyAirTemperature()
    {
        return $this->supplyAirTemperature;
    }

    /**
     * @param float $supplyAirTemperature
     */
    public function setSupplyAirTemperature($supplyAirTemperature)
    {
        $this->supplyAirTemperature = $supplyAirTemperature;
    }

    /**
     * @return boolean
     */
    public function isUnitStatus()
    {
        return $this->unitStatus;
    }

    /**
     * @param boolean $unitStatus
     */
    public function setUnitStatus($unitStatus)
    {
        $this->unitStatus = $unitStatus;
    }

    /**
     * @return float
     */
    public function getFanStatus()
    {
        return $this->fanStatus;
    }

    /**
     * @param float $fanStatus
     */
    public function setFanStatus($fanStatus)
    {
        if(is_numeric($fanStatus) && $fanStatus <= 100)
            $this->fanStatus = strval(number_format($fanStatus, 1));
        elseif(is_numeric($fanStatus) && $fanStatus > 100)
            $this->fanStatus = strval(number_format($fanStatus/10, 1));
        elseif(in_array($fanStatus, array('ON', 'On', 'Yes', 'on', 'yes', 1, '1'))) // 1 and '1' are old edge cases.
            $this->fanStatus = 'on';
        else
            $this->fanStatus = 'off';
//        $this->fanStatus = $fanStatus;
    }

    /**
     * @return float
     */
    public function getEnteringWaterTemperature()
    {
        return $this->enteringWaterTemperature;
    }

    /**
     * @param float $enteringWaterTemperature
     */
    public function setEnteringWaterTemperature($enteringWaterTemperature)
    {
        $this->enteringWaterTemperature = $enteringWaterTemperature;
    }

    /**
     * @return float
     */
    public function getLeavingWaterTemperature()
    {
        return $this->leavingWaterTemperature;
    }

    /**
     * @param float $leavingWaterTemperature
     */
    public function setLeavingWaterTemperature($leavingWaterTemperature)
    {
        $this->leavingWaterTemperature = $leavingWaterTemperature;
    }

    /**
     * @param string $property
     * @return string
     */
    public function printProperty($property)
    {
        if (property_exists($this, $property)) {
            return number_format($this->$property, 1);
        }
        else
            return '0';
    }

    public function isPropertySet($property)
    {
        switch($property) {
            case 'FAN':
                return isset($this->fanStatus);
                break;
            case 'CHWV':
                return isset($this->chillerValve);
                break;
            case 'RAH':
                return isset($this->returnAirHumidity);
                break;
            case 'RAT':
                return isset($this->returnAirTemperature);
                break;
            case 'SAT':
                return isset($this->supplyAirTemperature);
                break;
            case 'DAT':
                return isset($this->DAT);
                break;
            case 'EWT':
                return isset($this->enteringWaterTemperature);
                break;
            case 'LWT':
                return isset($this->leavingWaterTemperature);
                break;
        }
    }

    /**
     * @return mixed
     */
    public function getDeviceName()
    {
        return $this->deviceName;
    }

    /**
     * @param mixed $deviceName
     */
    public function setDeviceName($deviceName)
    {
        $this->deviceName = $deviceName;
    }

    /**
     * @return mixed
     */
    public function getAlertRAT()
    {
        return $this->alertRAT;
    }

    /**
     * @param mixed $alertRAT
     */
    public function setAlertRAT($alertRAT)
    {
        $this->alertRAT = $alertRAT;
    }

    /**
     * @return mixed
     */
    public function getAlertRAH()
    {
        return $this->alertRAH;
    }

    /**
     * @param mixed $alertRAH
     */
    public function setAlertRAH($alertRAH)
    {
        $this->alertRAH = $alertRAH;
    }

    /**
     * @return mixed
     */
    public function getAlertSAT()
    {
        return $this->alertSAT;
    }

    /**
     * @param mixed $alertSAT
     */
    public function setAlertSAT($alertSAT)
    {
        $this->alertSAT = $alertSAT;
    }

    /**
     * @return mixed
     */
    public function getAlertCHWV()
    {
        return $this->alertCHWV;
    }

    /**
     * @param mixed $alertCHWV
     */
    public function setAlertCHWV($alertCHWV)
    {
        $this->alertCHWV = $alertCHWV;
    }

    /**
     * @return mixed
     */
    public function getDAT()
    {
        return $this->DAT;
    }

    /**
     * @param mixed $DAT
     */
    public function setDAT($DAT)
    {
        $this->DAT = $DAT;
    }

}
