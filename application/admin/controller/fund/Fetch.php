<?php
/**
 * Created by PhpStorm.
 * User: dell pc
 * Date: 2018/4/17
 * Time: 12:45
 */

namespace app\admin\controller\fund;

use app\admin\model\FundIndexInfo;
use think\Controller;
use think\Db;
use think\Request;
use xirr\Calculator;

/**
 *
 *
 * @icon fa fa-circle-o
 */
class Fetch extends Controller
{
    const HS300 = "0003001";
    const CYBZ  = "3990062";
    const SZZS  = '0000011';
    const SZCZ  = '3990012';
    const ZXBZ  = '3990052';
    const SZ50  = '0000161';
    const BGZS  = '0000031';
    const AGZS  = '0000021';

    private $index_map = [
        self::HS300 => '沪深300',
        self::CYBZ  => '创业板指',
        self::SZZS  => '上证指数',
        self::SZCZ  => '深证成指',
        self::ZXBZ  => '中小板指',
        self::SZ50  => '上证50',
        self::BGZS  => 'B股指数',
        self::AGZS  => 'A股指数',
    ];

    public function __construct(Request $request = null)
    {
        parent::__construct($request);
        if (!IS_CLI and !is_inner_ip($_SERVER['REMOTE_ADDR'])) {
            exit;
        }
    }

    public function index()
    {
        $url_tpl = 'http://pdfm2.eastmoney.com/EM_UBG_PDTI_Fast/api/js?id=%s&TYPE=k&js=fsData%s((x))&rtntype=5&isCR=false&fsData%s=fsData%s';
        $macd_url_tpl = 'http://pdfm2.eastmoney.com/EM_UBG_PDTI_Fast/api/js?id=%s&TYPE=k&js=fsDataTeacma%s((x))&rtntype=5&extend=macd&isCR=false&check=kte&fsDataTeacma%s=fsDataTeacma%s';
        foreach ($this->index_map as $code => $name) {
            $micro_time = getMillisecond();
            $url = sprintf($url_tpl, $code, $micro_time, $micro_time, $micro_time);
            $macd_url = sprintf($macd_url_tpl, $code, $micro_time, $micro_time, $micro_time);
            $index_info = curl($url);
            $macd_info = curl($macd_url);
            $return = $this->formatCurlReturn($code, $name, $index_info, $macd_info);
        }

        echo 'success_'.ENV_STAGE, PHP_EOL;
        exit;
    }

    private function formatCurlReturn($code, $index_name, $data_index, $data_macd)
    {
        $data_index = preg_replace("/[a-zA-Z0-9]*[\(\)]/", '', $data_index);
        $data_index = json_decode($data_index, true)['data'];
        $data_macd = preg_replace("/[a-zA-Z0-9]*[\(\)]/", '', $data_macd);
        $data_macd = json_decode($data_macd, true)['data'];

        $mod_fund_index = Db::name('fund_index_info');
        $result = $mod_fund_index
            ->field(['date'])
            ->where(['code' => $code])
            ->order('date' , 'desc')
            ->limit(1)
            ->select();
        if ($result) {
            $start_date = $result[0]['date'];
        } else {
            $start_date = '0000-00-00';
        }
        $result = [];
        foreach ($data_index as $val) {
            $val = explode(',', $val);
            if ($val[0] > $start_date) {
                $result[$val[0]]['index'] = $val[2];
                $result[$val[0]]['turn_volume'] = str_replace('亿', '', $val[6]);
                if (strpos($result[$val[0]]['turn_volume'], '万') !== false ) {
                    $result[$val[0]]['turn_volume'] = (int)$result[$val[0]]['turn_volume'] / 10000;
                }
                $result[$val[0]]['code'] = $code;
                $result[$val[0]]['index_name'] = $index_name;
                $result[$val[0]]['date'] = $val[0];
            }
        }
        foreach ($data_macd as $val) {
            $val = explode(',', $val);
            $date = array_shift($val);
            $val = json_decode(implode(',', $val), true);
            if ($date > $start_date) {
                $result[$date]['dif']  = $val[0];
                $result[$date]['dea']  = $val[1];
                $result[$date]['macd'] = $val[2];
            }

        }

        foreach (array_chunk($result, 300) as $value) {
            $result = $mod_fund_index->insertAll($value);
        }
    }

    public function history()
    {
        $start_date = '2016-05-04';
        $end_date = date('Y-m-d');
        $mod_fund_index = Db::name('fund_index_info');
        $result = $mod_fund_index
            ->field(['date', 'index', 'macd', 'turn_volume'])
            ->where(['code'=>self::SZ50, 'status' => 1])
            ->order('date' , 'asc')
            ->select();
        $result = array_column($result, null, 'date');
        $account = array_column($result, 'turn_volume');
        rsort($account);
        $sell_amount = ($account[floor(count($account)/4)]);
        while (!isset($result[$start_date])) {
            $start_date = date('Y-m-d', strtotime(' +1 day ', strtotime($start_date)));
        }

        while (!isset($result[$end_date])) {
            $end_date = date('Y-m-d', strtotime(' -1 day ', strtotime($end_date)));
        }

        $start_index = $result[$start_date]['index'];
        $end_index   = $result[$end_date]['index'];


        $money = 10000;
        $add_money = 2000;
        $profit = 0;
        $have = false;
        $up_days = 0;
        $down_days = 0;
        $last_macd  = false;
        $last_index = false;
        $last_account = false;
        $buy_index = 0;
        $buy_date = 0;
        $total_have_days = 0;

        $can_buy = false;
        $can_sell = false;
        $can_add = false;

        $add_money_set = [];

        $tmp_profit = 0;

        foreach ($result as $date => $value) {
            if (strtotime($date) < strtotime($start_date)) {
                continue;
            }
            $macd  = $value['macd'];
            $index = $value['index'];
            $account = $value['turn_volume'];

            if ($last_macd === false) {
                $last_macd = $macd;
                $last_index = $index;
            }

            if ($can_buy) {
                $have = true;
                $buy_index = $index;
                $profit -= $money / 1000;
                $buy_date = $date;
                echo 'buy:'. $date. ' '. $index, PHP_EOL;
            }

            if ($can_add) {
                $add_money_set[] = [
                    'date'  => $date,
                    'index' => $index,
                    'money' => $add_money,
                ];
                $profit -= $add_money / 1000;
                echo 'add:'. $date. ' '. $index, PHP_EOL;
            }

            if ($can_sell) {
                $current_profit = - $money / 1000;
                $have = false;
                $ratio = 1 + ($index - $buy_index) / $buy_index;
                $cur_money = $money * $ratio;
                $profit += ($cur_money - $money);
                $current_profit += ($cur_money - $money);
                $have_days = (strtotime($date) - strtotime($buy_date)) / 3600 / 24;
                $total_have_days += $have_days + 2;
                if ($have_days > 7) {
                    $profit -= $cur_money / 2000;
                    $current_profit -= $cur_money / 2000;
                } else {
                    $profit -= $cur_money / 100 * 1.5;
                    $current_profit -= $cur_money / 100 * 1.5;
                }
                $profit -= $cur_money * 6 / 1000 * $have_days / 365;
                $current_profit -= $cur_money * 6 / 1000 * $have_days / 365;
//                list ($have_days, $current_profit, $profit) = $this->computeProfit();
                echo 'sell:'. $date. ' '. $index. ' '. ($have_days). ' '. (round($current_profit, 2)) . ' '.
                    $this->computeXirr($money, [$date => $money + $current_profit], $buy_date), PHP_EOL;
                $tmp_profit += $current_profit;
                if ($add_money_set) {
                    foreach ($add_money_set as $k => $v) {
                        $add_current_profit = - $add_money / 1000;
                        list($add_date, $add_index, $add_money) = array_values($v);
                        $add_radio = 1 + ($index - $add_index) / $add_index;
                        $add_cur_money = $add_money * $add_radio;
                        $profit += ($add_cur_money - $add_money);
                        $add_current_profit += ($add_cur_money - $add_money);
                        $add_have_days = (strtotime($date) - strtotime($add_date)) / 3600 / 24;
                        if ($add_have_days > 7) {
                            $profit -= $add_cur_money / 2000;
                            $add_current_profit -= $add_cur_money / 2000;
                        } else {
                            $profit -= $add_cur_money / 100 * 1.5;
                            $add_current_profit -= $add_cur_money / 100 * 1.5;
                        }
                        $profit -= $add_cur_money * 6 / 1000 * $add_have_days / 365;
                        $add_current_profit -= $add_cur_money * 6 / 1000 * $add_have_days / 365;
                        echo 'add_sell:'. $date. ' '. $index. ' '. ($add_have_days). ' '. (round($add_current_profit, 2)). ' '.
                            $this->computeXirr($add_money, [$date => $add_money + $add_current_profit], $add_date), PHP_EOL;
                        $tmp_profit += $add_current_profit;
                    }
                }
                $add_money_set = [];
            }

            if ($macd > $last_macd) {
                $up_days ++;
                $down_days = 0;
            } else {
                $down_days ++;
                $up_days = 0;
            }

            if ($last_macd < -20 and $up_days == 1) {
                if ($have) {
                    $can_add = ($index < $buy_index) ? true : false;
                    $can_buy = false;
                } else {
                    $can_add = false;
                    $can_buy = true;
                }
            } else {
                $can_buy = false;
                $can_add = false;
            }

            if ($last_macd > 0 and $have and $down_days == 1 and $last_account > $sell_amount) {
                $can_sell = true;
            } else {
                $can_sell = false;
            }


            $last_macd = $macd;
            $last_index = $index;
            $last_account = $account;
        }
        if ($have) {
            $current_profit = - $money / 1000;
            $total_have_days += (strtotime($end_date) - strtotime($buy_date)) / 3600 / 24;
            $ratio = 1 + ($index - $buy_index) / $buy_index;
            $cur_money = $money * $ratio;
            $profit += ($cur_money - $money);
            $current_profit += ($cur_money - $money);
            $have_days = (strtotime($date) - strtotime($buy_date)) / 3600 / 24;
            if ($have_days > 7) {
                $profit -= $cur_money / 2000;
                $current_profit -= $cur_money / 2000;
            } else {
                $profit -= $cur_money / 100 * 1.5;
                $current_profit -= $cur_money / 100 * 1.5;
            }
            $profit -= $cur_money * 6 / 1000 * $have_days / 365;
            $current_profit -= $cur_money * 6 / 1000 * $have_days / 365;
            echo '当前指数:'. $last_index, PHP_EOL;
            $tmp_profit += $current_profit;
        }

        $empty_days = (strtotime($end_date) - strtotime($start_date)) / 3600 / 24 - $total_have_days;
        echo '空闲天数:'. $empty_days, PHP_EOL;
        echo '持有天数:'.$total_have_days, PHP_EOL;
        $empty_profit = $empty_days * $money / 25 / 365;
        echo '合计利润:'. ($profit + $empty_profit), PHP_EOL;
        echo '空闲期间年化:'. ($empty_profit / $money / (($empty_days) / 365)), PHP_EOL;
        echo '持有期间年化:'. ($profit / $money / (($total_have_days) / 365)), PHP_EOL;
        echo '单次购买利润:'. ($money * (($end_index - $start_index) / $start_index)), PHP_EOL;
        echo '年化:'. (($profit + $empty_profit) / $money / (($total_have_days+$empty_days) / 365)), PHP_EOL;
        echo '单次购买年化:'. ($money * (($end_index - $start_index) / $start_index) / $money / (($total_have_days+$empty_days) / 365)), PHP_EOL;
        echo $tmp_profit + $empty_profit;
    }

    public function test()
    {
        $principal = 10000;
        $payment = 2000;
        $guess = 0.10;
        $startDate = '2017-04-18';
        $calculator = new Calculator();

        $payments = [
            '2017-05-31' => -$payment,
            '2017-06-30' => -$payment,
//            '2017-07-31' => -$payment,
            '2018-04-18' => 15000,
        ];

        $interest = $calculator->withSpecifiedPayments($principal, $startDate, $payments, $guess);

        echo $interest; // 0.084870
    }

    private function computeXirr($first_money, $add_money_set, $start_date)
    {
        $calculator = new Calculator();
        $radio = $calculator->withSpecifiedPayments($first_money, $start_date, $add_money_set, 0.01);
        return round($radio * 100 , 2). '%';
    }

    private function computeProfit()
    {

    }
}