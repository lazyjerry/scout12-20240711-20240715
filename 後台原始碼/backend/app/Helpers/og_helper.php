<?php

function getMetaValue($metas, $keys, $default_value = "")
{

    foreach ($keys as $key) {
        try {
            $value = (empty($value) && isset($metas[$key])) ? $metas[$key] : "";
            if (!empty($value)) {
                break;
            }
        } catch (Exception $e) {
            $value = "";
        }
    }

    $value = (empty($value)) ? $default_value : $value;
    return $value;
}

function getHeadMeta(string $url, string $title, string $content, array $margeArray = [])
{
    /* Initiate curl */
    $ch = curl_init();

    /* Disable SSL verification */
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

    /* Will return the response */
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

    /* Set the Url */
    curl_setopt($ch, CURLOPT_URL, $url);

    /* Set the headers */
    // curl_setopt($ch, CURLOPT_HTTPHEADER, ['Client-ID: ' . $this->client_id]);
    curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US; rv:1.8.1.13) Gecko/20080311 Firefox/2.0.0.13');

    /* Execute */
    $contents = curl_exec($ch);

    /* Close */
    curl_close($ch);

    $temp_data = [];
    if (isset($contents) && is_string($contents)) {

        $pattern = '/ itemprop=\"[\w]+\"/i';
        $contents = preg_replace($pattern, '', $contents);
        $contents = str_replace("'", '"', $contents);

        // error_log($contents);

        preg_match_all('/<[\s]*meta[\s]*(name|property)="?' . '([^>"]*)"?[\s]*' . 'content="?([^>"]*)"?[\s]*[\/]?[\s]*>/si', $contents, $match);

        // error_log(json_encode($match[1], JSON_UNESCAPED_UNICODE));
        // error_log(json_encode($match[2], JSON_UNESCAPED_UNICODE));
        // error_log(json_encode($match[3], JSON_UNESCAPED_UNICODE));
        if (isset($match) && is_array($match)) {
            $originals = $match[0];
            $names = $match[2];
            $values = $match[3];

            if (count($originals) == count($names) && count($names) == count($values)) {

                for ($i = 0, $limiti = count($names); $i < $limiti; $i++) {
                    $temp_data[strtolower($names[$i])] = $values[$i];
                }
            }
        }
        $contents = ''; // TODO 空間太大了，之後需要優化或是另存
        $result = array(
            'meta_cache' => json_encode($temp_data),
            'source_content' => $contents,
            'create_time' => date('Y-m-d H:i:s'),
        );
    }

    $result['title'] = getMetaValue($temp_data, ['og:title', 'title'], $title);

    $result['description'] = getMetaValue($temp_data, ['og:description', 'description'], $content);
    $result['image'] = getMetaValue($temp_data, ['og:image', 'image', 'twitter:image'], "");
    $result['keywords'] = getMetaValue($temp_data, ['news_keywords', 'keywords', 'tags'], "");
    $result['author'] = getMetaValue($temp_data, ['author', 'source'], "");

    if (!empty($margeArray)) {
        $result = array_merge($result, $margeArray);
    }
    return $result;
}
