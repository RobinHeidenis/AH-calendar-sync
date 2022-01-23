<?php
include('./consts.php');
$config = json_decode(file_get_contents('./status.json'), true);
include('./mailer.php');

if ($config["run"] != "true") {
    echo "Script execution suspended, activation required";
    mailer("reminder: AH script suspended", "The ah calendar script is deactivated.\r\nThis may have been done in error.");
    exit;
}

$pwd = $config["password"];

$appointmentsdata = GetDataFromAH();

ParseToJSONFile($appointmentsdata);

function CurlCall($url, $post, $id, $followRedirects)
{

    $ch = curl_init();

    if ($post) {
        curl_setopt_array($ch, array(
            CURLOPT_URL => $url,
            CURLOPT_COOKIEJAR => "./scheduledata/cookie.txt",
            CURLOPT_COOKIEFILE => "./scheduledata/cookie.txt",
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CUSTOMREQUEST => "POST",
            CURLOPT_POSTFIELDS => $post,
            CURLOPT_FOLLOWLOCATION => $followRedirects,
            CURLOPT_CONNECTTIMEOUT => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTPHEADER => array(
                "content-type: application/x-www-form-urlencoded"
            )
        ));
    } else
        curl_setopt_array($ch, array(
            CURLOPT_URL => $url,
            CURLOPT_COOKIEJAR => "./scheduledata/cookie.txt",
            CURLOPT_COOKIEFILE => "./scheduledata/cookie.txt",
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => $followRedirects,
            CURLOPT_CONNECTTIMEOUT => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_CUSTOMREQUEST => "GET"
        ));

    $result = curl_exec($ch);

    if (curl_errno($ch)) {
        if (curl_errno($ch) == 28 || curl_errno($ch) == 6) {
            writeStatus("false", "Timeout reached. Server may be down. Restart after manual check.");
            mailer("AH SCRIPT TIMEOUT", "The ah calendar script has timed out.\r\nThe execution of the script has now been stopped.\r\nPlease check the error file for more information.\r\nThe script can be resumed after manual confirmation of the availableness of the server.\r\n\r\nERROR NUMBER:\r\n" . curl_errno($ch) . "\r\nERROR:\r\n" . curl_error($ch));
            echo "Timeout reached. Server may be down. Execution stopped";
            exit;
        } else {
            writeStatus("false", "Unexpected error encountered. Check the logs for more information.");
            mailer("AH SCRIPT ERROR", "The ah calendar script has encountered an unexpected error.\r\nPlease review the error and error number.\r\nThe errors can also be viewed in the logfile on the server.\r\n\r\nERROR NUMBER:\r\n" . curl_errno($ch) . "\r\nERROR:\r\n" . curl_error($ch));
            echo 'Error:' . curl_error($ch);
            echo 'Error Number:' . curl_errno($ch);
            exit;
        }
    }

    curl_close($ch);

    return $result;
}

function GetDataFromAH()
{
    global $pwd;

    $pattern_relayState = '#name="RelayState" value="(.*)" />#';
    $pattern_SAMLRequest = '#name="SAMLRequest" value="(.*)" />#s';
    $pattern_SAMLResponse = '#name="SAMLResponse" value="(.*)" />#s';

    $result = CurlCall('https://sam.ahold.com/isam/sps/AD_Europe_SAM_SP/saml20/logininitial?NameIdFormat=Email&Target=https://sam.ahold.com:443/wrkbrn_jct/etm/time/timesheet/etmTnsMonth.jsp',
        null,
        'call1',
        false
    );

    preg_match_all($pattern_relayState, $result, $RelayStateMatches);
    preg_match_all($pattern_SAMLRequest, $result, $SAMLRequestMatches);

    $RelayState1 = urlencode(str_replace(array("\n", "\r"), '', $RelayStateMatches[1][0]));
    $SAMLRequest = urlencode(str_replace(array("\n", "\r"), '', $SAMLRequestMatches[1][0]));

    $result = CurlCall('https://euidp.aholddelhaize.com/isam/sps/AD_Europe/saml20/login',
        'RelayState=' . $RelayState1 . '&SAMLRequest=' . $SAMLRequest,
        'call2',
        true
    );

    $result = CurlCall('https://euidp.aholddelhaize.com/pkmslogin.form',
        "username=" . SAM_USERNAME . "&password=" . $pwd . "&login-form-type=pwd&token=Unknown&authz=",
        'call3',
        true
    );

    evaluateResponse($result);

    preg_match_all($pattern_relayState, $result, $RelayStateMatches);
    preg_match_all($pattern_SAMLResponse, $result, $SAMLResponseMatches);

    $RelayState2 = urlencode(str_replace(array("\n", "\r"), '', $RelayStateMatches[1][0]));
    $SAMLResponse = urlencode(str_replace(array("\n", "\r"), '', $SAMLResponseMatches[1][0]));

    $result = CurlCall('https://sam.ahold.com/isam/sps/AD_Europe_SAM_SP/saml20/login',
        'RelayState=' . $RelayState2 . '&SAMLResponse=' . $SAMLResponse,
        'rooster_ah',
        true
    );

    $result .= CurlCall('https://sam.ahold.com/wrkbrn_jct/etm/time/timesheet/etmTnsMonth.jsp',
        'NEW_MONTH_YEAR=' . date("m/Y", strtotime("last day of next month")),
        'rooster_ah_next',
        true
    );

    if (file_exists("./scheduledata/cookie.txt"))
        unlink("./scheduledata/cookie.txt");

    return $result;
}

function ParseToJSONFile($appointmentsdata)
{

    $pattern = '#(calendarCellRegularFuture|calendarCellRegularCurrent)(.*?)</table>#s';
    $matches = array();
    preg_match_all($pattern, $appointmentsdata, $matches);

    $pattern_date = '#Details van (\d\d\/\d\d\/\d\d\d\d)\'"><tr>#s';
    $pattern_time = '#<SPAN>(\d\d:\d\d)</SPAN>#s';

    $scheduled = array();
    foreach ($matches[0] as $i => $item) {
        $times = array();
        preg_match($pattern_date, $item, $date);
        preg_match_all($pattern_time, $item, $times);

        if (!empty($date[1]) && !empty($times[1][0]) && !empty($times[1][1])) {
            $scheduled[$i]["date"] = $date[1];
            $scheduled[$i]["starttime"] = $times[1][0];
            $scheduled[$i]["endtime"] = $times[1][1];
        }
    }

    if (empty($scheduled)) {
        writeStatus("false", "No appointments found. Something might be wrong?");
        mailer("AH SCRIPT ERROR", "There were no appointments found for this and next month.\r\nThis could be an error with the script.\r\nTo be sure, script execution has been stopped.\r\nThis could also be a false positive.\r\nCheck manually to be sure.");
    }

    file_put_contents("./scheduledata/appointments_ah.json", json_encode($scheduled));

}

function writeStatus($run, $message)
{
    $original_json = json_decode(file_get_contents("./status.json"), true);

    $status["password"] = $original_json["password"];
    $status["run"] = $run;
    $status["message"] = $message;
    file_put_contents("./status.json", json_encode($status));
}

function evaluateResponse($result)
{
    if (strpos($result, '<h2>Password Expired</h2>') !== false) {
        echo 'Password change required. Please change the password and update in the config.';
        writeStatus("false", "Password change required");
        mailer("AH SCRIPT ERROR", "Your password has expired.\r\nPlease reset your password through the online interface and update the password in the config,\r\nOr on the web interface.\r\nThe script cannot run without the new password.");
        exit;
    }
}

?>