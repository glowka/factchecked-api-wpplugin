<?php

add_action( 'admin_menu', 'factchecked_api_menu' );

function factchecked_api_menu() {
	add_submenu_page( 'tools.php', 'Zbierz wzmianki z treści artykułów', 'Zbierz wzmianki', 'manage_options', 'extract_sources', 'extract_sources_page');
}

function extract_sources_page() {
  $the_query = new WP_Query(array(
    'post_type' => 'dmg_statements',
    'posts_per_page' => -1
  ));

  echo "<p>Wyciaganie zrodel z opisow wypowiedzi:</p>";
  $meat_only = isset($_GET['meat']);

  while ($the_query->have_posts()) {
    $the_query->the_post();

    echo '<p>';
    if (!$meat_only)
      echo 'Przetwarzam <a href="'. get_edit_post_link() . '">' . get_the_title() . '</a><br/>';

    $intro = get_post_meta(get_the_ID(), 'intro_statement', true);
    preg_match_all('/href="([^"]+)"/', $intro, $matches);

    $source_old = get_post_meta(get_the_ID(), 'main-fc-source', true);
    $skip_main_source = get_post_meta(get_the_ID(), 'main-fc-source-skip', true);
    if ($matches[1]) {
      $source_found = $matches[1][0];
      if ($source_old) {
        if ($source_old != $source_found) {
          echo "<span style='color:blue;'>  Zostawiam $source_old choc znalazlem $source_found</span><br/>";

        } else if (!$meat_only) {
          echo "  <span style='color:blue;'>Pozostawiam $source_old</span><br/>";
        }
      } else { // Inserting new
        if (count($matches[1]) > 1 && !$skip_main_source) {
          echo "<span style='color:red;'>Znalazlem kilka URLi, musisz <a href='".get_edit_post_link()."'>recznie</a> ustawic jeden jako glowny:<br/>";
          foreach($matches[1] as $m) {
            echo "&nbsp;&nbsp;" . $m ."<br/>";
          }
          echo '</span>';

        } else {
          update_field('main-fc-source', $source_found);
          echo "<span style='color:green;'>Wstawiam glowny $source_found</span><br/>";
        }
      }
    } else if (!$skip_main_source) {
      echo "<span style='color:red;'>Nie znaleziono odnosnika we <a href='".get_edit_post_link()."'>wprowadzeniu</a>!</span><br/>";
    }

    // przetwarzanie poszczegolnych wypowiedzi
    $politic_statement_pos = -1;
    while(have_rows('politic_statement')) {
      $politic_statement_pos++;
      the_row();
      $statement = get_sub_field('statement_content', false);

      $existing = array();
      $subupdated = false;
      $subsources_data = get_sub_field('source-urls');
      foreach($subsources_data as $source) {
        $existing[$source['statement-fc-source']] = true;
      }

      preg_match_all('/href="([^"]+)"/', $statement, $subsources);
      foreach($subsources[1] as $new) {
        if (isset($existing[$new])) {
          if (!$meat_only)
            echo "<span style='color:blue;padding-left:15px;'>Pozostawiam $new</span><br/>";

        } else {
          if (preg_match('/^https:\/\/twitter.com\/[^\/]+$/', $new)) {
            // skipping twitter general profiles
            continue;
          }

          $subupdated = true;
          $existing[$new] = true;
          array_push($subsources_data, array(
            'statement-fc-source' => $new
          ));

          update_or_insert_meta('politic_statement_'. $politic_statement_pos .'_source-urls_'. (count($subsources_data) - 1).'_statement-fc-source', $new);
          update_or_insert_meta('_politic_statement_'. $politic_statement_pos .'_source-urls_'. (count($subsources_data) - 1).'_statement-fc-source', 'field_5bd33f462b004');

          echo "<span style='color:green;padding-left:15px;'>Wstawiam poboczny $new</span><br/>";
        }
      }

      if ($subupdated) {
        update_or_insert_meta('politic_statement_'. $politic_statement_pos .'_source-urls', count($subsources_data));
      }
    }

    echo '</p>';
  }
}

function update_or_insert_meta($key, $value) {
  global $wpdb;
  $c = $wpdb->get_col($wpdb->prepare('SELECT count(*) FROM '. $wpdb->prefix .'postmeta where post_id = %d and meta_key = %s', get_the_ID(), $key));

  if ($c[0]) {
    $wpdb->update($wpdb->prefix . 'postmeta', array(
             'meta_value' => $value
           ), array(
             'post_id' => get_the_ID(),
             'meta_key' => $key
           ));
  } else {
    $wpdb->insert( $wpdb->prefix . 'postmeta', array(
      'post_id' => get_the_ID(),
      'meta_key' => $key,
      'meta_value' => $value
     ));
  }
}
