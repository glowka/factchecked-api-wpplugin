<?php

class Faktograf implements iSite {
    public function get_statement_query() {
        return array(
            'category_name' => 'ocjena-tocnosti',
            'meta_key' => 'tocnost', // rating is provided
            'meta_compare' => '>',
            'meta_value' => 0 // and statement is rated
        );
    }

    public function get_statement($post_id) {
        $rating = get_post_meta($post_id, 'tocnost', true);

        $st = new Statement();
        $st->id = $post_id;
        $st->factchecker_uri = get_permalink();
        // $st->explanation = apply_filters('the_content', get_the_content());

        $text = explode(':', get_the_title(), 2);
        $st->text = (count($text) > 1 ? trim($text[1]) : $text[0]);
        //$st->explanation = get_the_content();
        $st->rating = array(
            '0' => 'Nije ocijenjeno',
            '1' => 'Ni F od fakta',
            '2' => 'Ni pola fakta',
            '3' => 'Polufakt',
            '4' => 'Tri kvarta fakta',
            '5' => 'Fakt'
        )[$rating]; // TODO handle exception rating
        $st->rating_img = get_template_directory_uri() . '/dist/images/ico/ico_' . $rating . '.png';

        $sources = get_field('sources_urls', $post_id);
        if ($sources) {
            $st->sources = array_map(function($s) { return $s['source_url']; }, $sources);
        }

        return $st;
    }

    public function get_sources_list() {
        global $wpdb;
        // meta_key format: sources_urls_0_source_url
        $sources = $wpdb->get_col( "SELECT DISTINCT meta_value FROM ". $wpdb->prefix ."postmeta WHERE meta_key LIKE 'sources\_urls\_%'");

        return new SourceList($sources);
    }
}