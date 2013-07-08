<?php

/**
 *
 * allow for pseudo imitation of global $wp to access parse_request 
 * 
 * @link http://stackoverflow.com/a/10741227/1542064
 */

class tribe_ombed_service extends WP {

    /*
     *  Overwrite the parse_request function to allow for
     *  processing of urls defined in variables
    */

    function parse_request( $url ) {
        global $wp_rewrite;

        //validate url
        if(empty($url))
            return false;

        //sanitize
        $url = filter_var($url, FILTER_SANITIZE_URL);

        $this->query_vars = array();
        $post_type_query_vars = array();

        // Process PATH_INFO, REQUEST_URI, and 404 for permalinks.

        // Fetch the rewrite rules.
        $rewrite = $wp_rewrite->wp_rewrite_rules();

        if ( ! empty($rewrite) ) {
            // If we match a rewrite rule, this will be cleared.
            $error = '404';
            $this->did_permalink = true;

            //parse url
            $_url = parse_url($url);

            //set path info
            $pathinfo = $_url['path'];
            $pathinfo_array = explode('?', $pathinfo);
            $pathinfo = str_replace("%", "%25", $pathinfo_array[0]);

            //req_uri
            $req_uri = $_url['path'];
            $req_uri_array = explode('?', $req_uri);
            $req_uri = $req_uri_array[0];

            //self
            $self = $_url['path'];
            $home_path = parse_url(home_url());
            if ( isset($home_path['path']) )
                $home_path = $home_path['path'];
            else
                $home_path = '';
            $home_path = trim($home_path, '/');

            // Trim path info from the end and the leading home path from the
            // front.  For path info requests, this leaves us with the requesting
            // filename, if any.  For 404 requests, this leaves us with the
            // requested permalink.
            $req_uri = str_replace($pathinfo, '', $req_uri);
            $req_uri = trim($req_uri, '/');
            $req_uri = preg_replace("|^$home_path|", '', $req_uri);
            $req_uri = trim($req_uri, '/');
            $pathinfo = trim($pathinfo, '/');
            $pathinfo = preg_replace("|^$home_path|", '', $pathinfo);
            $pathinfo = trim($pathinfo, '/');
            $self = trim($self, '/');
            $self = preg_replace("|^$home_path|", '', $self);
            $self = trim($self, '/');

            // The requested permalink is in $pathinfo for path info requests and
            //  $req_uri for other requests.
            if ( ! empty($pathinfo) && !preg_match('|^.*' . $wp_rewrite->index . '$|', $pathinfo) ) {
                $request = $pathinfo;
            } else {
                // If the request uri is the index, blank it out so that we don't try to match it against a rule.
                if ( $req_uri == $wp_rewrite->index )
                    $req_uri = '';
                $request = $req_uri;
            }

            $this->request = $request;

            // Look for matches.
            $request_match = $request;
            if ( empty( $request_match ) ) {
                // An empty request could only match against ^$ regex
                if ( isset( $rewrite['$'] ) ) {
                    $this->matched_rule = '$';
                    $query = $rewrite['$'];
                    $matches = array('');
                }
            } else if ( $req_uri != 'wp-app.php' ) {
                foreach ( (array) $rewrite as $match => $query ) {
                    // If the requesting file is the anchor of the match, prepend it to the path info.
                    if ( ! empty($req_uri) && strpos($match, $req_uri) === 0 && $req_uri != $request )
                        $request_match = $req_uri . '/' . $request;

                    if ( preg_match("#^$match#", $request_match, $matches) ||
                        preg_match("#^$match#", urldecode($request_match), $matches) ) {

                        if ( $wp_rewrite->use_verbose_page_rules && preg_match( '/pagename=\$matches\[([0-9]+)\]/', $query, $varmatch ) ) {
                            // this is a verbose page match, lets check to be sure about it
                            if ( ! get_page_by_path( $matches[ $varmatch[1] ] ) )
                                continue;
                        }

                        // Got a match.
                        $this->matched_rule = $match;
                        break;
                    }
                }
            }

            if ( isset( $this->matched_rule ) ) {
                // Trim the query of everything up to the '?'.
                $query = preg_replace("!^.+\?!", '', $query);

                // Substitute the substring matches into the query.
                $query = addslashes(WP_MatchesMapRegex::apply($query, $matches));

                $this->matched_query = $query;

                // Parse the query.
                parse_str($query, $perma_query_vars);

                // If we're processing a 404 request, clear the error var
                // since we found something.
                unset( $_GET['error'] );
                unset( $error );
            }

            // If req_uri is empty or if it is a request for ourself, unset error.
            if ( empty($request) || $req_uri == $self || strpos($_url['path'], 'wp-admin/') !== false ) {
                unset( $_GET['error'] );
                unset( $error );

                if ( isset($perma_query_vars) && strpos($_url['path'], 'wp-admin/') !== false )
                    unset( $perma_query_vars );

                $this->did_permalink = false;
            }
        }

        $this->public_query_vars = apply_filters('query_vars', $this->public_query_vars);

        foreach ( $GLOBALS['wp_post_types'] as $post_type => $t )
            if ( $t->query_var )
                $post_type_query_vars[$t->query_var] = $post_type;

        foreach ( $this->public_query_vars as $wpvar ) {
            if ( isset( $this->extra_query_vars[$wpvar] ) )
                $this->query_vars[$wpvar] = $this->extra_query_vars[$wpvar];
            elseif ( isset( $_POST[$wpvar] ) )
                $this->query_vars[$wpvar] = $_POST[$wpvar];
            elseif ( isset( $_GET[$wpvar] ) )
                $this->query_vars[$wpvar] = $_GET[$wpvar];
            elseif ( isset( $perma_query_vars[$wpvar] ) )
                $this->query_vars[$wpvar] = $perma_query_vars[$wpvar];

            if ( !empty( $this->query_vars[$wpvar] ) ) {
                if ( ! is_array( $this->query_vars[$wpvar] ) ) {
                    $this->query_vars[$wpvar] = (string) $this->query_vars[$wpvar];
                } else {
                    foreach ( $this->query_vars[$wpvar] as $vkey => $v ) {
                        if ( !is_object( $v ) ) {
                            $this->query_vars[$wpvar][$vkey] = (string) $v;
                        }
                    }
                }

                if ( isset($post_type_query_vars[$wpvar] ) ) {
                    $this->query_vars['post_type'] = $post_type_query_vars[$wpvar];
                    $this->query_vars['name'] = $this->query_vars[$wpvar];
                }
            }
        }

        // Convert urldecoded spaces back into +
        foreach ( $GLOBALS['wp_taxonomies'] as $taxonomy => $t )
            if ( $t->query_var && isset( $this->query_vars[$t->query_var] ) )
                $this->query_vars[$t->query_var] = str_replace( ' ', '+', $this->query_vars[$t->query_var] );

        // Limit publicly queried post_types to those that are publicly_queryable
        if ( isset( $this->query_vars['post_type']) ) {
            $queryable_post_types = get_post_types( array('publicly_queryable' => true) );
            if ( ! is_array( $this->query_vars['post_type'] ) ) {
                if ( ! in_array( $this->query_vars['post_type'], $queryable_post_types ) )
                    unset( $this->query_vars['post_type'] );
            } else {
                $this->query_vars['post_type'] = array_intersect( $this->query_vars['post_type'], $queryable_post_types );
            }
        }

        foreach ( (array) $this->private_query_vars as $var) {
            if ( isset($this->extra_query_vars[$var]) )
                $this->query_vars[$var] = $this->extra_query_vars[$var];
        }

        if ( isset($error) )
            $this->query_vars['error'] = $error;

        $this->query_vars = apply_filters('request', $this->query_vars);

        do_action_ref_array('parse_request', array(&$this));

    }

}