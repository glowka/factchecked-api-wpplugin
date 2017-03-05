<?php

class Demagog implements iSite {
    public function get_statement_query() {
        return array(
            'post_type' => 'dmg_statements',
            'meta_key' => 'politic_statement',
        );
    }

    public function get_statement($statement_id) {
        if (!preg_match('/^(\d+)_(\d+)$/', $statement_id, $post_and_statement_id)) {
            throw new Exception("not found"); // TODO
        }

        global $wpdb;

        $st = new Statement();
        $st->id = $statement_id;
        $st->factchecker_uri = get_site_url() . '?p=' . $post_and_statement_id[1];

        $statement_inpost_str = (string) $post_and_statement_id[2];

        $sources = array();
        $sql = 'select meta_value FROM '. $wpdb->prefix .'postmeta where ' .
        'post_id = ' . $post_and_statement_id[1] .
        ' AND meta_key = "main-fc-source";';

        $main_source = $wpdb->get_var($sql);
        if ($main_source) {
            $sources[$main_source] = 1;
        }

        // get post metadata
        $sql = 'select meta_key, meta_value FROM '. $wpdb->prefix .'postmeta where ' .
        'post_id = ' . $post_and_statement_id[1] .
        ' AND LEFT(meta_key, '. (19 + strlen($statement_inpost_str)) .') = "politic_statement_'. $statement_inpost_str .'_";';

        $data = $wpdb->get_results($sql);
        foreach ($data as $d) {
            if (endsWith($d->meta_key, 'statement_content')) {
                $st->text = wp_strip_all_tags($d->meta_value);

            } else if (endsWith($d->meta_key, 'statement_rank')) {
                $st->rating = $d->meta_value;
                $st->rating_img = $this->get_rating_img($st->rating);

            } else if (endsWith($d->meta_key, 'statement_rank_desc')) {
                $st->explanation = $d->meta_value;

            } else if (endsWith($d->meta_key, 'fc-source')) {
                $sources[$d->meta_value] = 1;
            }
        }

        if ($sources) {
            $st->sources = array_keys($sources);
        }

        return $st;
    }

    // TODO <blockquote class="twitter-twe

    public function get_rating_img($rating) {
        if ($rating == 'lie') {
            return 'http://demagog.org.pl/wp-content/themes/demagog/assets/img/ico-exlamation-enabled.png';

        } else if ($rating == 'true') {
            return 'http://demagog.org.pl/wp-content/themes/demagog/assets/img/ico-yes-enabled.png';

        } else if ($rating == 'false') {
            return 'http://demagog.org.pl/wp-content/themes/demagog/assets/img/ico-no-enabled.png';

        } else if ($rating == 'unknown') {
            return 'http://demagog.org.pl/wp-content/themes/demagog/assets/img/ico-question-enabled.png';

        } else {
            return null;
        }
    }

    public function get_statements($source_url) {
        global $wpdb;

//        +---------+---------+-------------------------------------------------------+---------------------+
//        | meta_id | post_id | meta_key                                              | LEFT(meta_value,10) |
//        +---------+---------+-------------------------------------------------------+---------------------+
//        |   39660 |    3826 | politic_statement                                     | 1                   |
//        |   39710 |    3826 | politic_statement_0_statement_politic                 | 2333                |
//        |   39712 |    3826 | politic_statement_0_statement_content                 | <span styl          |
//        |   39714 |    3826 | politic_statement_0_statement_rank                    | false               |
//        |   39716 |    3826 | politic_statement_0_statement_rank_desc               | <span styl          | (uzasadnienie)
//        |   40240 |    3826 | politic_statement_0_statement_tag                     |                     |
//        |   40242 |    3826 | politic_statement_0_statement_category                |                     |
//        |   40252 |    3826 | politic_statement_0_source-urls_0_statement-fc-source | http://www          |
//        |   40254 |    3826 | politic_statement_0_source-urls_1_statement-fc-source | http://www          |
//        |   40256 |    3826 | politic_statement_0_source-urls                       | 2                   |
//        |   40250 |    3826 | main-fc-source                                         | http://www          |
//        +---------+---------+-------------------------------------------------------+---------------------+
        // select meta_id, post_id, meta_key, LEFT(meta_value,10)  from wp_postmeta where meta_key LIKE 'politic\_statement%' and post_id = 3826;

        if ($source_url) {
          $escaped = esc_sql($source_url);

          $sql = 'select distinct post_id, LEFT(meta_key, 24) as meta_key FROM '. $wpdb->prefix .'postmeta where RIGHT(meta_key, 20) = "_statement-fc-source" ' .
            ' AND meta_value = "'. $escaped .'"';

          $wypowiedzi = $wpdb->get_results($sql);

          $statement_ids = array();
          foreach($wypowiedzi as $w) {
              $meta_in_post = explode('_', $w->meta_key);
              $statement_ids[$w->post_id . '_' . $meta_in_post[2]] = true;
          }

          // get wypowiedzi with maain source set
          $sql = 'select post_id FROM '. $wpdb->prefix .'postmeta where meta_key = "main-fc-source" and meta_value = "'. $escaped .'"';
          $post_ids = $wpdb->get_col($sql);

          $sql = 'select post_id, meta_value FROM '. $wpdb->prefix .'postmeta where meta_key = "politic_statement" and post_id IN ('. join(',', $post_ids) .')';
          $wypowiedzi = $wpdb->get_results($sql);

          foreach($wypowiedzi as $w) {
              for ($i = 0; $i < $w->meta_value; $i++) {
                  $statement_ids[$w->post_id . '_' . $i] = true;
              }
          }

          print_r($statement_ids);

          $statements = array();
          foreach(array_keys($statement_ids) as $id) {
              array_push($statements, $this->get_statement($id));
          }

          return $statements;

        } else {
          // all statements
          $sql = 'select distinct post_id, LEFT(meta_key, 24) as meta_key FROM '. $wpdb->prefix .'postmeta where LEFT(meta_key, 18) = "politic_statement_" and RIGHT(meta_key, 18) = "_statement_content" LIMIT 10';

          // TODO range of $sql = 'select distinct post_id, meta_value as statement_count FROM '. $wpdb->prefix .'postmeta where meta_key = "politic_statement" LIMIT 10';
          // but it's going to be hard to paginate

          // TODO actually it should only be those containing links..
            // get all IDs and then filter by those having ids either here or there
            // it would be eaasier to have separate tables and update hooks

          $wypowiedzi = $wpdb->get_results($sql);

          $statements = array();
          foreach($wypowiedzi as $w) {

              $st = new Statement();

              $meta_in_post = explode('_', $w->meta_key);
              $st->id = $w->post_id . '_' . $meta_in_post[2];
              $st->factchecker_uri = get_site_url() . '?p=' . $w->post_id;

              array_push($statements, $st);
          }

          return $statements;
        }
    }

    public function get_sources_list() {
        global $wpdb;
        // meta_key ends in fc-source for sources' urls
        $sources = $wpdb->get_col( 'SELECT DISTINCT meta_value FROM '. $wpdb->prefix .'postmeta where RIGHT(meta_key,9) = "fc-source" and not LEFT(meta_key,1) = "_"');

        return new SourceList($sources);
    }
}

function startsWith($haystack, $needle)
{
     $length = strlen($needle);
     return (substr($haystack, 0, $length) === $needle);
}

function endsWith($haystack, $needle)
{
    $length = strlen($needle);
    if ($length == 0) {
        return true;
    }

    return (substr($haystack, -$length) === $needle);
}