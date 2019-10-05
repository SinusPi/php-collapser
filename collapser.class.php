<?php
class Table_Collapser {
	var $config = [];
	var $styles_printed=false;
	var $scripts_printed=false;

	var $registered_identifiers=[];

	var $collapsed_in=[];

	function __construct($config) {
		$this->config = $config;
	}

	function _($s) { return chr(0)."*".chr(0).$s; }
	
	function print_styles() {
		?>
		<style type="text/css">
		.tc-collapsed {
			background:#eee;
		}
		.recurly_table {
			border-collapse: collapse;
		}
		.recurly_table,.recurly_table td,.recurly_table th {
			border:1px solid gray;
		}
		.recurly_table td {
			vertical-align: top;
		}
		</style>
		<?php
		$this->styles_printed=true;
	}
	
	function print_scripts() {
		?>
		<script type="text/javascript" src="https://code.jquery.com/jquery-3.4.1.min.js"></script>
		<script type="text/javascript">
			$(function() {
				$(".expand_stub")
					.not(".scripted")
					.each(
						function() { $(this).attr("data-recurly-href",$(this).attr("href")); })
					.attr("href","#")
					.text("[expand]")
					.click(function(ev) {
						ev.preventDefault();
						console.log("Loading "+$(this).attr("href"));
						$(this).closest("td").parent().closest("td").load($(this).attr("data-recurly-href"));
					})
					.addClass("scripted");
			});
			function expand(collapsed_class) {
				$("."+collapsed_class).toggle();
			}
		</script>
		<?php
		$this->scripts_printed=true;
	}

	function collapse ($c, &$arr) {
		$any_set=false;
		foreach ($c['input'] as $i) if (isset($arr[$i])) $any_set=true;
		if (!$any_set) return;

		if (is_callable($c['formatter']))
			$arr[$c['output']] = $c['formatter']($arr);
		elseif (is_string($c['format'])) {
			$inputs = [];
			foreach ($c['input'] as $i) $inputs[$i]=$arr[$i];
			$arr[$c['output']] = vsprintf($c['format'],$inputs);
		} else {
			// leave it alone?
			$arr[$c['output']] = "";
		}
		
		foreach ($c['input'] as $f) {
			$this->collapsed_in[$c['output']][$f]=$arr[$f];
			unset($arr[$f]);
		}
	}

	function register_arrayfier($class,$arrayfier) {
		$this->config['classes'][$class]['arrayfier']=$arrayfier;
	}

	function arrayobject_identify($arrobj) {
		foreach ($this->registered_identifiers as $identifier) {
			$class = $identifier($arrobj);
			if ($class) return $class;
		}
	}

	function show($obj,&$visited=null,$path="") {

		if (!$this->styles_printed) $this->print_styles();
		if (!$this->scripts_printed) $this->print_scripts();


		// prevent circular referenes

		if ($visited==null) $visited=[$path=>&$obj];
		elseif ($k=array_search($obj,$visited,TRUE)) return ['__type'=>(is_object($obj)?get_class($obj):'array'),'__seen'=>$k];


		// identify object type, if not Object

		if (is_object($obj)) $class=get_class($obj);
		else $class=$this->arrayobject_identify($obj);
		if (!$class && !is_array($obj)) $class=gettype($obj); // keep arrays "typeless" for better display, lest everything would be 'array'


		// arrayfy

		$array = [];
		$arrayfied=false;
		if ($this->config['classes'])
			foreach ($this->config['classes'] as $c=>&$cfgclass)
				if ($cfgclass['arrayfier'] && ((is_object($obj) && is_subclass_of($obj,$c)) || ($c==$class))) {
					$meta = $cfgclass['arrayfier']($obj,$array);
					$arrayfied=true;
				}
		if (!$arrayfied) $array = (array)$obj;


		// maybe it's just a string.

		if (!is_array($array)) {
			echo $array;
			return;
		}
		
		
		// collapse fields

		$this->collapsed_in=[];
		if ($this->config['classes'])
			foreach ($this->config['classes'] as $c=>&$cfgclass) {
				if ((is_object($obj) && is_subclass_of($obj,$c)) || ($c==$class)) {
					if ($cfgclass['collapse'])
						foreach ($cfgclass['collapse'] as $collapse)
							$this->collapse($collapse,$array);
					if ($cfgclass['format'])
						foreach ($cfgclass['format'] as $format)
							if (isset($array[$format['field']])) {
								if (is_callable($format['formatter']))
									$array[$format['field']]=$format['formatter']($array);
								elseif (is_string($format['format']))
									$array[$format['field']]=sprintf($format['format'],$array[$format['field']]);
							}
				}
			}


		//echo "<pre>"; print_r($array); echo "</pre>";

		// OUTPUT

		$header = $meta['header'] ?: "<div class='recurly_type'>".$class."</div>";
		echo $header;

		echo "<table class='recurly_table'>";

		$groups = $this->config['classes'][$class]['groups'];
		if ($groups) {
			echo "<tbody class='groups'>";
			foreach ($groups as $gh=>$gkeys) {
				echo "<tr><th>$gh</th></tr>";
				foreach ($gkeys as $gkey) {
					if (isset($array[$gkey])) {
						$this->print_row($gkey,$array[$gkey]);
						unset($array[$gkey]);
					}
				}
			}
			echo "</tbody>";
			echo "<tr><th>...</th></tr>";
		}
		ksort($array);
		foreach ($array as $k=>$v)
			$this->print_row($k,$v);


		/*
		if ($array['links']) {
			print_row('links',$array['links']);
		}
		*/
		echo "</table>";

	}

	function print_row_valtd($v) {
		if (is_string($v)) echo "<td class='recurly_string'>$v</td>";
		elseif (is_numeric($v)) echo "<td class='recurly_numeric'>$v</td>";
		elseif (is_array($v)) { echo "<td class='array'>"; $tc=clone $this; $tc->show($v,$visited,$path."->".$k); echo "</td>"; }
		elseif (is_object($v)) { echo "<td class='object'>"; $tc=clone $this; $tc->show($v,$visited,$path."->".$k); echo "</td>"; }
		else { echo "<td class='recurly_else'>$v</td>"; }
	}
	function print_row_plain($k,$v,$indent,$collapsed_class=null) {
		if ($collapsed_class)
			echo "<tr class='tc-collapsed $collapsed_class' style='display:none;'>";
		else
			echo "<tr>";
		echo "<td>".str_repeat("&nbsp;|&nbsp;",$indent)."$k</td>";
		$this->print_row_valtd($v);
		echo "</tr>";
	}
	function print_row_collapsed($k,$v,$indent=0) {
		static $uidn=0;
		$uidn++;
		$uid="collapsed_".uniqid()."_$uidn";
		echo "<tr>";
		echo "<td>".str_repeat("| ",$indent)."<i><b>$k</b></i> <a href='javascript:expand(\"$uid\");'>⮞</a></td>";
		$this->print_row_valtd($v);
		echo "</tr>";
		foreach ((array)$this->collapsed_in[$k] as $ck=>&$cv) {
			$this->print_row($ck,$cv,$indent+1,$uid);
		}
	}
	function print_row($k,$v,$indent=0,$collapsed_class=null) {
		if (isset($this->collapsed_in[$k])) {
			$this->print_row_collapsed($k,$v,$indent,$collapsed_class);
		} else {
			$this->print_row_plain($k,$v,$indent,$collapsed_class);
		}
	}
}
