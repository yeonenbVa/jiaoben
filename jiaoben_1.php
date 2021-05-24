#!/usr/bin/php -q
<?php
//echo phpinfo();die;
//error_reporting(0); 
checkQuestion();

function checkQuestion($topicId = "") {
    $file_name1 = date("Y-m-d");
    $file_name2 = date("H-i-s");
    $path = "./" . $file_name1 . "-math-" . $file_name2 . ".txt";
    $msg_info = "";
//    file_put_contents($path, "");
    $use_type = array();
    $use_type[1] = '先行测试';
    $use_type[2] = '高效学习';
    $use_type[3] = '竞赛拓展';
    $use_type[4] = '招生抓手';
    $use_type[5] = '学习检测';
    $standard_num = 3;
    if (!$topicId) {
        $topicList = getTopicList();
    } else {
        $topicList[]["id"] = $topicId;
    }
    foreach ($topicList as $key => $value) {
        echo $value["id"] . "\n";
        $tid = $value["id"];
        $topic_info = getTopicByTopicId($value["id"]);
        $tag_code_list = [];
        $zhlx_list = [];
        $zhlx_info = [];
        if (isset($topic_info["kmap_code_list"]["200"])) {
            $tag_code_list = getKmapInfoByKmapCode($topic_info["kmap_code_list"]["200"]["kmap_code"]);
        }
        if (isset($topic_info["kmap_code_list"]["265"])) {
            $zhlx_list = getKmapInfoByKmapCode($topic_info["kmap_code_list"]["265"]["kmap_code"]);
        }
        foreach ($tag_code_list as $key => $value) {
            $tag_code_info[$key]["tag_code"] = $value["tag_code"];
        }
        foreach ($zhlx_list as $k => $val) {
            $zhlx_info[$k]["tag_name"] = $val["tag_code"] . "  (专题Code)";
            $zhlx_info[$k]["tag_code"] = $val["tag_code"];
        }
        $return_data["knowledge_map"] = $tag_code_info;
        $return_data["zhlx_map"] = $zhlx_info;
        foreach ($tag_code_info as $key => $val) {
            echo $val["tag_code"];
            for ($i = 1; $i < 6; $i++) {
                $QuestionsByKnowledge = getQuestionsByKnowledge($val["tag_code"], $i, "", $tid);
                $num = count($QuestionsByKnowledge);
                $data_return[$key][$i]["num"] = $num;
                if ($i == 4) {
                    continue;
                }
                if ($i == 3) {
                    $data_return[$key][$i]["pass"] = 1;
                    continue;
                }
                if ($num < $standard_num) {
                    $msg_info .= "错误---专题id:" . $tid . ",知识点:" . $val["tag_code"] . ",用途:" . $use_type[$i] . ",题量为" . $num . ",少于要求:" . $standard_num . "<hr>";
                }
            }
        }
        if (!empty($zhlx_info)) {
            foreach ($zhlx_info as $val) {
                echo $val["tag_code"];
                $QuestionsByKnowledge = getQuestionsByKnowledge($val["tag_code"], 3, "", $tid);
                $num = count($QuestionsByKnowledge);
                $data_return[$key][$i]["num"] = $num;
                if ($num < $standard_num) {
                    $msg_info .= "错误---专题id:" . $tid . ",知识点:" . $val["tag_code"] . ",用途:" . $use_type[$i] . ",题量为" . $num . ",少于要求:" . $standard_num . "<hr>";
                }
            }
        }
    }
    file_put_contents($path, $msg_info, FILE_APPEND);
    echo 'over';
    die;
}

function getQuestionByIdAnalyse1($question_id) {
    $return_data = getQuestionById($question_id);
    $return_data = $return_data['data'];
    $msg = '';
    if ($return_data == false) {
        $msg .= "接口返回数据为null\n";
    } else {
        if (isset($return_data['id']) == false || $return_data['id'] == false) {
            $msg .= "错误==试题id为null\n";
        }
        if (isset($return_data['content']) == false || $return_data['content'] == false) {
            $msg .= "错误==试题内容为null\n";
        }
        if (isset($return_data['q_type']) == false || $return_data['q_type'] == false) {
            $msg .= "错误==试题类型null或为空\n";
        }
        if (isset($return_data['answer']) == false || $return_data['answer'] == false) {
            $msg .= "错误==试题没有正确答案\n";
        }
        if (isset($return_data['content']) && $return_data['q_type'] == 2 && !is_numeric(strpos(htmlspecialchars_decode($return_data['content']), '##$$##'))) {

            $preg = "/[_]+[1-9]*[_]+/";
            preg_match_all($preg, $return_data['content'], $result);
            $num1 = count($result[0]);
            $preg = "/##\\$\\$##/";
            preg_match_all($preg, $return_data['content'], $result);
            $num2 = count($result[0]);
            $num = $num1 + $num2;
            if ($num != 0) {
                if (isset($return_data['answer'])) {
                    $answer_num = count($return_data['answer']);
                    if ($num != $answer_num) {
                        $msg .= "错误==填空题题目的答案数和题干中的特殊替换符号数量不符合,答案是: $answer_num  个,但题干中的替换符号数为: $num\n";
                    }
                } else {
                    $msg .= "错误==题目没有正确答案\n";
                }
            } else {
                $msg .= "错误==填空题题目中没有包含填空符号 ##$$## 或者 ___*___\n";
            }
        }
        if ((!isset($return_data['options']) || $return_data['options'] == null) && ($return_data['q_type'] == 1 || $return_data['q_type'] == 3)) {
            $msg .= "错误==选择题目没有选项\n";
        } elseif (!is_array($return_data['options'])) {
            $msg .= "错误==选择题目选项格式不正确\n";
        }

        if (isset($return_data['answer']) && (count($return_data['answer']) == 0)) {
            $msg .= "错误==题目没有正确答案\n";
        }
        if (isset($return_data['analyze'])) {
            if (empty($return_data['analyze'])) {
                $msg .= "分布解析为null\n";
            } else {

                foreach ($return_data['analyze'] as $k => $v) {
                    if (isset($v['content']) && empty($v['content'])) {
                        $msg .= "分布解析为null\n";
                    } else {
                        
                    }
                }
            }
        }

        if (isset($return_data['analyze']) && count($return_data['analyze']) <= 0) {
            $msg .= "错误==分布解析数据类型为空\n";
        }
    }
    return $msg;
}

function getQuestionsByKnowledge($knowledge) {

    $param['knowledge'] = $knowledge;
    $url = "http://input-math-t.51xonline.com/v2/api/getQuestionsByKnowledge";
    $return_data = rpc_request($url, $param);

    return $return_data;
}

function getQuestionById($question_id) {
    $param['question_id'] = $question_id;
    $url = "http://input-math-t.51xonline.com/v2/api/getQuestionById";
    $return_data = rpc_request($url, $param);
    return $return_data;
}

function getTopicByTopicId($topicId) {
    if ($topicId) {
        $param = array();
        $param['tid'] = $topicId;
        //根据知识点获取试题.
        $url = "http://api-topic.51yxedu.com/math/v2/getTopicByTopicId";
        $return_data = rpc_request($url, $param);
        if (empty($return_data)) {
            
        }
        return $return_data['data'];
    }
}

function rpc_request($url, $param, $method = "post", $ret_json = true) {
    //设置选项
    $opts = array(
        CURLOPT_TIMEOUT => 60,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_URL => $url,
            //CURLOPT_USERAGENT      => $_SERVER['HTTP_USER_AGENT'],
    );
    if ($method === 'post') {
        $opts[CURLOPT_POST] = 1;
        $opts[CURLOPT_POSTFIELDS] = $param;
    }

    //初始化并执行curl请求
    $ch = curl_init();
    curl_setopt_array($ch, $opts);
    $data = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE); // code 码
    if ($httpCode > 399) {
        
    }

    curl_close($ch);
    if ($ret_json) {
        $return_data = json_decode($data, true);
    }
    return $return_data;
}

function getTopicList() {
    $param = array();
    //根据知识点获取试题.
    $url = "http://api-topic.51yxedu.com/math/v2/getTopicList";
    $param['type'] = 3;
    $return_data = rpc_request($url, $param);
//            var_dump($return_data);die;
    if (empty($return_data)) {
        
    }
    return $return_data['data'];
}

function getKmapInfoByKmapCode($kmap_code) {
    $param = array();
    //根据知识点获取试题.
    $url = "http://api-topic.51yxedu.com/math/v2/getKmapInfoByKmapCode";
    $param['kmap_code'] = $kmap_code;
    $return_data = rpc_request($url, $param);
    if (empty($return_data)) {
        
    }
    return $return_data['data'];
}
?>
