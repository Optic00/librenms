<?php

$no_refresh = true;

?>

<div class="row">
    <div class="col-sm-12">
        <span id="message"></span>
    </div>
</div>
<?php
if (isset($_POST['create-default'])) {
    $default_rules[] = array(
        'device_id' => '-1',
        'rule'      => '%macros.device_down = "1"',
        'severity'  => 'critical',
        'extra'     => '{"mute":false,"count":"-1","delay":"300"}',
        'disabled'  => 0,
        'name'      => 'Devices up/down',
    );
    $default_rules[] = array(
        'device_id' => '-1',
        'rule'      => '%devices.uptime < "300" && %macros.device = "1"',
        'severity'  => 'critical',
        'extra'     => '{"mute":false,"count":"1","delay":"300"}',
        'disabled'  => 0,
        'name'      => 'Device rebooted',
    );
    $default_rules[] = array(
        'device_id' => '-1',
        'rule'      => '%bgpPeers.bgpPeerState != "established" && %macros.device_up = "1"',
        'severity'  => 'critical',
        'extra'     => '{"mute":false,"count":"1","delay":"300"}',
        'disabled'  => 0,
        'name'      => 'BGP Session down',
    );
    $default_rules[] = array(
        'device_id' => '-1',
        'rule'      => '%bgpPeers.bgpPeerFsmEstablishedTime < "300" && %bgpPeers.bgpPeerState = "established"',
        'severity'  => 'critical',
        'extra'     => '{"mute":false,"count":"1","delay":"300"}',
        'disabled'  => 0,
        'name'      => 'BGP Session established',
    );
    $default_rules[] = array(
        'device_id' => '-1',
        'rule'      => '%macros.port_down = "1"',
        'severity'  => 'critical',
        'extra'     => '{"mute":false,"count":"1","delay":"300"}',
        'disabled'  => 0,
        'name'      => 'Port status up/down',
    );
    $default_rules[] = array(
        'device_id' => '-1',
        'rule'      => '%macros.port_usage_perc >= "80" && %macros.port_up = "1" && %macros.port = "1"',
        'severity'  => 'critical',
        'extra'     => '{"mute":false,"count":"-1","delay":"300"}',
        'disabled'  => 0,
        'name'      => 'Port utilisation over threshold',
    );
    $default_rules[] = array(
        'device_id' => '-1',
        'rule'      => '%sensors.sensor_current > %sensors.sensor_limit && %sensors.sensor_alert = "1"',
        'severity'  => 'critical',
        'extra'     => '{"mute":false,"count":"-1","delay":"300"}',
        'disabled'  => 0,
        'name'      => 'Sensor over limit',
    );
    $default_rules[] = array(
        'device_id' => '-1',
        'rule'      => '%sensors.sensor_current < %sensors.sensor_limit_low && %sensors.sensor_alert = "1"',
        'severity'  => 'critical',
        'extra'     => '{"mute":false,"count":"-1","delay":"300"}',
        'disabled'  => 0,
        'name'      => 'Sensor under limit',
    );
    foreach ($default_rules as $add_rule) {
        dbInsert($add_rule, 'alert_rules');
    }
}//end if

require_once 'includes/modal/new_alert_rule.inc.php';
require_once 'includes/modal/delete_alert_rule.inc.php';
?>
<form method="post" action="" id="result_form">
<?php
if (isset($_POST['results_amount']) && $_POST['results_amount'] > 0) {
    $results = $_POST['results'];
}
else {
    $results = 50;
}

echo '<div class="table-responsive">
    <table class="table table-hover table-condensed" width="100%">
    <tr>
    <th>#</th>
    <th>Name</th>
    <th>Rule</th>
    <th>Severity</th>
    <th>Status</th>
    <th>Extra</th>
    <th>Enabled</th>
    <th>Action</th>
    </tr>';

echo '<td colspan="7">';
if ($_SESSION['userlevel'] >= '10') {
    echo '<button type="button" class="btn btn-primary btn-sm" data-toggle="modal" data-target="#create-alert" data-device_id="'.$device['device_id'].'"><i class="fa fa-plus"></i> Create new alert rule</button>';
}

echo '</td>
    <td><select name="results" id="results" class="form-control input-sm" onChange="updateResults(this);">';
$result_options = array(
    '10',
    '50',
    '100',
    '250',
    '500',
    '1000',
    '5000',
);
foreach ($result_options as $option) {
    echo "<option value='$option'";
    if ($results == $option) {
        echo ' selected';
    }

    echo ">$option</option>";
}

echo '</select></td>';

$count_query = 'SELECT COUNT(id)';
$full_query  = 'SELECT *';
$sql         = '';
$param       = array();
if (isset($device['device_id']) && $device['device_id'] > 0) {
    $sql   = 'WHERE (device_id=? OR device_id="-1")';
    $param = array($device['device_id']);
}

$query       = " FROM alert_rules $sql ORDER BY id ASC";
$count_query = $count_query.$query;
$count       = dbFetchCell($count_query, $param);
if (!isset($_POST['page_number']) && $_POST['page_number'] < 1) {
    $page_number = 1;
}
else {
    $page_number = $_POST['page_number'];
}

$start      = (($page_number - 1) * $results);
$full_query = $full_query.$query." LIMIT $start,$results";

foreach (dbFetchRows($full_query, $param) as $rule) {
    $sub   = dbFetchRows('SELECT * FROM alerts WHERE rule_id = ? ORDER BY `state` DESC, `id` DESC LIMIT 1', array($rule['id']));
    $ico   = 'ok';
    $col   = 'success';
    $extra = '';
    if (sizeof($sub) == 1) {
        $sub = $sub[0];
        if ((int) $sub['state'] === 0) {
            $ico = 'ok';
            $col = 'success';
        }
        elseif ((int) $sub['state'] === 1 || (int) $sub['state'] === 2) {
            $ico   = 'remove';
            $col   = 'danger';
            $extra = 'danger';
        }
    }

    $alert_checked = '';
    $orig_ico      = $ico;
    $orig_col      = $col;
    $orig_class    = $extra;
    if ($rule['disabled']) {
        $ico   = 'pause';
        $col   = '';
        $extra = 'active';
    }
    else {
        $alert_checked = 'checked';
    }

    $rule_extra = json_decode($rule['extra'], true);
    if ($rule['device_id'] == ':-1' || $rule['device_id'] == '-1') {
        $popover_msg = 'Global alert rule';
    }
    else {
        $popover_msg = 'Device specific rule';
    }
    echo "<tr class='".$extra."' id='row_".$rule['id']."'>";
    echo '<td><i>#'.((int) $rule['id']).'</i></td>';
    echo '<td>'.$rule['name'].'</td>';
    echo "<td class='col-sm-4'>";
    if ($rule_extra['invert'] === true) {
        echo '<strong><em>Inverted</em></strong> ';
    }

    echo '<i>'.htmlentities($rule['rule']).'</i></td>';
    echo '<td>'.$rule['severity'].'</td>';
    echo "<td><span id='alert-rule-".$rule['id']."' class='glyphicon glyphicon-".$ico.' glyphicon-large text-'.$col."'></span> ";
    if ($rule_extra['mute'] === true) {
        echo "<span class='glyphicon glyphicon-volume-off glyphicon-large text-primary' aria-hidden='true'></span></td>";
    }

    echo '<td><small>Max: '.$rule_extra['count'].'<br />Delay: '.$rule_extra['delay'].'<br />Interval: '.$rule_extra['interval'].'</small></td>';
    echo '<td>';
    if ($_SESSION['userlevel'] >= '10') {
        echo "<input id='".$rule['id']."' type='checkbox' name='alert-rule' data-orig_class='".$orig_class."' data-orig_colour='".$orig_col."' data-orig_state='".$orig_ico."' data-alert_id='".$rule['id']."' ".$alert_checked." data-size='small' data-content='".$popover_msg."' data-toggle='modal'>";
    }

    echo '</td>';
    echo '<td>';
    if ($_SESSION['userlevel'] >= '10') {
        echo "<button type='button' class='btn btn-primary btn-sm' data-toggle='modal' data-target='#create-alert' data-device_id='".$rule['device_id']."' data-alert_id='".$rule['id']."' name='edit-alert-rule' data-content='".$popover_msg."'><span class='glyphicon glyphicon-pencil' aria-hidden='true'></span></button> ";
        echo "<button type='button' class='btn btn-danger btn-sm' aria-label='Delete' data-toggle='modal' data-target='#confirm-delete' data-alert_id='".$rule['id']."' name='delete-alert-rule' data-content='".$popover_msg."'><span class='glyphicon glyphicon-trash' aria-hidden='true'></span></button>";
    }

    echo '</td>';
    echo "</tr>\r\n";
}//end foreach

if (($count % $results) > 0) {
    echo '    <tr>
        <td colspan="8" align="center">'.generate_pagination($count, $results, $page_number).'</td>
        </tr>';
}

echo '</table>
    <input type="hidden" name="page_number" id="page_number" value="'.$page_number.'">
    <input type="hidden" name="results_amount" id="results_amount" value="'.$results.'">
    </form>
    </div>';

if ($count < 1) {
    if ($_SESSION['userlevel'] >= '10') {
        echo '<div class="row">
            <div class="col-sm-12">
            <form role="form" method="post">
            <p class="text-center">
            <button type="submit" class="btn btn-success btn-lg" id="create-default" name="create-default">Create default global alerts!</button>
            </p>
            </form>
            </div>
            </div>';
    }
}

?>

<script>
$("[data-toggle='modal'], [data-toggle='popover']").popover({
    trigger: 'hover',
        'placement': 'top'
});
$('#ack-alert').click('', function(e) {
    event.preventDefault();
    var alert_id = $(this).data("alert_id");
    $.ajax({
        type: "POST",
            url: "ajax_form.php",
            data: { type: "ack-alert", alert_id: alert_id },
            success: function(msg){
                $("#message").html('<div class="alert alert-info">'+msg+'</div>');
                if(msg.indexOf("ERROR:") <= -1) {
                    setTimeout(function() {
                        location.reload(1);
                    }, 1000);
                }
            },
                error: function(){
                    $("#message").html('<div class="alert alert-info">An error occurred acking this alert.</div>');
                }
    });
});

$("[name='alert-rule']").bootstrapSwitch('offColor','danger');
$('input[name="alert-rule"]').on('switchChange.bootstrapSwitch',  function(event, state) {
    event.preventDefault();
    var $this = $(this);
    var alert_id = $(this).data("alert_id");
    var orig_state = $(this).data("orig_state");
    var orig_colour = $(this).data("orig_colour");
    var orig_class = $(this).data("orig_class");
    $.ajax({
        type: 'POST',
            url: 'ajax_form.php',
            data: { type: "update-alert-rule", alert_id: alert_id, state: state },
            dataType: "html",
            success: function(msg) {
                if(msg.indexOf("ERROR:") <= -1) {
                    if(state) {
                        $('#alert-rule-'+alert_id).removeClass('glyphicon-pause');
                        $('#alert-rule-'+alert_id).addClass('glyphicon-'+orig_state);
                        $('#alert-rule-'+alert_id).removeClass('text-default');
                        $('#alert-rule-'+alert_id).addClass('text-'+orig_colour);
                        $('#row_'+alert_id).removeClass('active');
                        $('#row_'+alert_id).addClass(orig_class);
                    } else {
                        $('#alert-rule-'+alert_id).removeClass('glyphicon-'+orig_state);
                        $('#alert-rule-'+alert_id).addClass('glyphicon-pause');
                        $('#alert-rule-'+alert_id).removeClass('text-'+orig_colour);
                        $('#alert-rule-'+alert_id).addClass('text-default');
                        $('#row_'+alert_id).removeClass('warning');
                        $('#row_'+alert_id).addClass('active');
                    }
                } else {
                    $("#message").html('<div class="alert alert-info">'+msg+'</div>');
                    $('#'+alert_id).bootstrapSwitch('toggleState',true );
                }
            },
                error: function() {
                    $("#message").html('<div class="alert alert-info">This alert could not be updated.</div>');
                    $('#'+alert_id).bootstrapSwitch('toggleState',true );
                }
    });
});

function updateResults(results) {
    $('#results_amount').val(results.value);
    $('#page_number').val(1);
    $('#result_form').submit();
}

function changePage(page,e) {
    e.preventDefault();
    $('#page_number').val(page);
    $('#result_form').submit();
}

</script>
