<?php

/**
 * DateSelect form control.
 *
 * @author     Roman Novák
 * @version    0.1
 */
class DateSelect extends /*Nette\Forms\*/FormControl
{

	static $defaultFormat = '%year%/%month%/%day%/%hour/%minute%';
	static $formatSeparator = '/';
	static $emptyValue = '-';

	protected $value;
	protected $format;
	protected $skipFirst;
	protected $formats = array(
		'year' => array(
			'modifier' => 'Y',
			'default' => 'Y',
			'separator' => '-',
			'strp' => array('%Y', 'tm_year'),
			'range' => array('-10 years', '-0 years'),
			'sequence' => 1),
		'month' => array(
			'modifier' => 'm',
			'default' => 'm',
			'separator' => '-',
			'strp' => array('%m', 'tm_mon'),
			'range' => array(1, 12),
			'sequence' => 1),
		'day' => array(
			'modifier' => 'd',
			'default' => 'd',
			'separator' => '',
			'strp' => array('%d', 'tm_mday'),
			'range' => array(1, 31),
			'sequence' => 1),
		'hour' => array(
			'modifier' => 'G',
			'default' => 'G',
			'separator' => ':',
			'strp' => array('%H', 'tm_hour'),
			'range' => array(0, 23),
			'sequence' => 1),
		'minute' => array(
			'modifier' => 'i',
			'default' => 'i',
			'separator' => ':',
			'strp' => array('%M', 'tm_min'),
			'range' => array(0, 59),
			'sequence' => 1),
		'second' => array(
			'modifier' => 's',
			'default' => 's',
			'separator' => '',
			'strp' => array('%S', 'tm_sec'),
			'range' => array(0, 59)),
			'sequence' => 1);	

	/**
	 * @param  string  label
	 * @param  int  width of the control
	 * @param  int  maximum number of characters the user may enter
	 */
	public function __construct($label, $format = null)
	{
		parent::__construct($label);
		if(null === $format) {
			$format = self::$defaultFormat;
		}
		$this->format = $format;
	}

	public function skipFirst()
	{
		$this->skipFirst = true;
		return $this;
	}

	public function getValue()
	{
		$values = parent::getValue();
		$value = $this->parseValuesArray($values);
		return $value;
	}

	public function setFormat($format)
	{
		$this->format = (string)$format;
	}

	protected function parseValuesArray($values)
	{
		$value = '';
		$separator = '';
		foreach($this->formats as $format => $options) {
			if(isset($values[$format])) {
				$value .= $separator . ($values[$format] < 10 ? '0' . $values[$format] : $values[$format]);
				$separator = $options['separator'];
			}
		}
		return $value;
	}
	
	protected function parseValueFormat()
	{
		$return = '';
		$f = explode(self::$formatSeparator, $this->format);
		$formats = array();
		foreach($f as $format) {
			$formats[] = strtolower(trim($format, '%'));
		}
		foreach($this->formats as $name => $format) {
			if(in_array(strtolower($name), $formats)) {
				$format['strp'][0];
				$return .= $format['strp'][0] . $format['separator'];
			}
		}
		$return = trim(trim($return, '-'), ':');
		return $return;
	}

	/**
	 * Sets control's value.
	 * @param  string
	 * @return void
	 */
	public function setValue($value)
	{
		if(!is_array($value)) {
			$value = strptime($value, $this->parseValueFormat()); // '%Y-%m-%d %H:%M'
		}
		if(isset($value['tm_year'])) {
			$value['tm_year'] += 1900;
		}
		return parent::setValue($value);
	}

	/**
	 * Generates control's HTML element.
	 * @return Html
	 */
	public function getControl()
	{
		$formats = explode(self::$formatSeparator, $this->format);

		$container = Html::el('span');
		
		foreach($formats as $index => $format) {
			$format = trim($format, '%');
			/*if(0 === $index) {
				$this->getLabelPrototype()->id = $this->getHtmlId() . '-' . $format;
			}*/
			$control = Html::el('select');
			$control->id = $this->getHtmlId() . '-' . $format;
			$control->name = $this->getHtmlName() . '[' . $format . ']';
			if(!isset($this->formats[$format])) {
				continue;
			}
			$modifier = $this->formats[$format]['modifier'];
			$default = $this->formats[$format]['default'];
			$range = $this->formats[$format]['range'];
			$strp = $this->formats[$format]['strp'][1];
			$sequence = $this->formats[$format]['sequence'];
			$from = is_numeric($range[0]) ? $range[0] : (int)date($default, strtotime($range[0]));
			$to = is_numeric($range[1]) ? $range[1] : (int)date($default, strtotime($range[1]));
			$current = $from;

			$options = array();
		
			if($this->skipFirst) {
				$options[''] = self::$emptyValue;
			}

			$cycles = 0;
			do {
				$options[$current] = $modifier === $default ? $current : date($modifier, $current);
				$current+= $sequence;
				$cycles++;
				if(100 == $cycles) {
					break;
					die('oops');
				}
			} while($to >= $current);

			if($this->skipFirst) {
				for($i = 1; $i < strlen(end($options));++$i) {
					$options[''] .= self::$emptyValue;
				}
			}

			foreach($options as $option) {
				$opt = $control->create('option')
					->setValue($option)
					->setText($option);

				if(isset($this->value[$strp]) && $this->value[$strp] == $option) {
					$opt->selected = 'selected';
				}
				elseif(isset($this->value[$format]) && $this->value[$format] == $option) {
					$opt->selected = 'selected';
				}
			}
			$container->add($control);
		}
		return $container;
	}

	public function __call($name, $args)
	{
		foreach(array('modifier', 'range', 'sequence') as $type) {
			if(preg_match('#set([a-zA-Z]+)' . ucfirst($type) . '#i', $name, $matched)) {

				$key = strtolower($matched[1]);
				if(isset($this->formats[$key])) {
					switch($type) {
						case 'modifier':
							if(isset($args[0])) {
								$this->formats[$key][$type] = $args[0];
							}
							break;
						case 'range':
							foreach(range(0, 1) as $index) {
								if(isset($args[$index])) {
									$this->formats[$key][$type][$index] = $args[$index];
								}
							}
							break;
					}
					return $this;
				}
			}
		}
		return parent::__call($name, $args);
	}

	public static function extend() {
		FormContainer::extensionMethod('FormContainer::addDateSelect', array(__CLASS__, 'addDateSelect'));
	}
	
	public static function addDateSelect(FormContainer $sender, $name, $label, $format = null)
	{
		return $sender[$name] = new self($label, $format);
	}
}
