<?php

abstract class StatementPerPostSite implements iSite {

    abstract public function post_to_statement();
    abstract public function get_statement_query();

    public function get_statement($statement_id) {
        $params = $this->get_statement_query();
        $params['p'] = $statement_id;

        $the_query = new WP_Query($params);

        if (!$the_query->have_posts()) {
            // TODO array('errors' => array("Fact-check with id=$id doesn't exist."))
            // TODO raise error
            return API::output(null, 404);
        }

        $the_query->the_post();

        return $site->post_to_statement();
    }

    public function get_statements($source) {
        // todo implement source

        $meta_query_args = array(
            'relation' => 'OR', // Optional, defaults to "AND"
            array(
                'key'     => '_my_custom_key',
                'value'   => 'Value I am looking for',
                'compare' => '='
            )
        );
        $meta_query = new WP_Meta_Query( $meta_query_args );
        $mq_sql = $meta_query->get_sql(
            'post',
            $wpdb->posts,
            'ID'
        );

        $params = $this->get_statement_query();
        $params['posts_per_page'] = -1; // all posts
        // offset
        $the_query = new WP_Query($params);

        $statements = array();
        while ($the_query->have_posts()) {
            global $post;
            $the_query->the_post();

            $st = $site->get_statement(get_the_ID());;
            array_push($statements, $st);
        }

        wp_reset_postdata();

        // $q->found_posts will give you 20
        // $q->max_num_pages will give you 4

        return $statements;
    }
}