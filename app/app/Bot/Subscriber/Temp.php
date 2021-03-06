<?php

namespace Bot\Subscriber;

class Temp
{
    protected $topics = array(
        '/devices/thermostat/controls/Floor_1' => 'floor1',
        '/devices/thermostat/controls/Floor_2' => 'floor2',
        '/devices/thermostat/controls/Basement' => 'basement',
        '/devices/sensor_1/controls/Temperature' => 'floor1_temp',
        '/devices/sensor_2/controls/Temperature' => 'floor2_temp',
        '/devices/sensor_0/controls/Temperature' => 'basement_temp',
        '/devices/wb-w1/controls/28-001416f342ff' => 'garret_temp',
        '/devices/wb-w1/controls/28-001417357bff' => 'outside_temp',
        '/devices/wb-w1/controls/28-0014175dd1ff' => 'bathhouse_temp',
        '/devices/boiler/controls/Current Temperature' => 'boiler_temp',
        '/devices/thermostat/controls/Simple' => 'simple',
        '/devices/thermostat/controls/Work time' => 'work_time',
        '/devices/thermostat/controls/Pressure' => 'pressure',
        '/devices/thermostat/controls/Enabled' => 'enabled',
        '/devices/relays/controls/K8' => 'boiler_relay',
        '/devices/relays/controls/K1' => 'floor1_pump',
        '/devices/relays/controls/K2' => 'floor2_pump',
        '/devices/relays/controls/K3' => 'basement_pump',
        '/devices/water_supply/controls/Heater' => 'water_heater',
        '/devices/water_supply/controls/Pressure' => 'water_pressure',
    );
    protected $processed_topics = [];

    protected $mqtt;
    protected $widgets;

    public function __construct($mqtt, $widgets)
    {
        $this->mqtt = $mqtt;
        $this->widgets = $widgets;
    }


    public function run()
    {
        if(!$this->mqtt->connect()){
            error_log('Failed to connect to MQTT (temp)');
            return false;
        }

        $topics = array(
            '/devices/+/controls/+' => array(
                'qos' => 0,
                'function' => array($this, 'processmsg')
            )
        );

        $this->generateControls();
        $this->mqtt->subscribe($topics, 0);

        $time_start = time();
        $timeout = 10;
        while ($this->mqtt->proc()) {
            if (time() - $time_start > $timeout) {
                $this->mqtt->close();
                return false;
            }
        }

        return $this->processed_topics;
    }

    public function processmsg($topic, $msg)
    {
        if (array_key_exists($topic, $this->processed_topics)) {
            error_log("processed $topic"); // FIXME: mqtt hangs without pushing to log WTF!
            $this->processed_topics[$topic] = $msg;
        }

        foreach ($this->processed_topics as $t => $val) {
            if (is_null($val)) {
                return;
            }
        }

        $this->mqtt->close();
    }

    public function generateControls()
    {
        foreach ($this->widgets as $widget) {
            foreach ($widget['controls'] as $control) {
                $this->processed_topics[$control["statusTopic"]] = null;
            }
        }
    }
}
