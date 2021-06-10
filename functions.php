<?php
/*
 * sp functions and definitions
 */

//require_once('.TCPDF/tcpdf.php');

define("MAIN_TEAM_ID",  229);
define('YOUNG_TEAM_ID', 1457);
define('YOUNG_TEAM3_ID',1688);
define('SHOP_LINK', get_field('shop_link', 86));
define('NO_IMG', get_field('no_img_pers', 86));


function dd($dump){
    if($_GET["TEST"] == 1){
      echo '<pre>';
      var_dump($dump);
      echo '</pre>';
    }

}

add_action('wp_ajax_filterTournamentTable', 'filterTournamentTable');
add_action('wp_ajax_nopriv_filterTournamentTable', 'filterTournamentTable');

function filterTournamentTable() {
    if(!empty($_POST)) {
        $url     = wp_get_referer();
        $post_id = url_to_postid( $url );
        $data = $_POST;

        $curr_season_id  = $data['season'];
        $curr_league_id  = $data['league'];
        $selected_sostav = $data['sostav'];
        $curr_league = false;

        // Получение сезонов у которых есть таблицы
        $seasons = get_terms_in_post('sp_season', ['sp_table', 'sp_tournament']);

        // Получаем все составы
        $compositions_non_filter = get_field('vybor_sostavov', $post_id);
        $compositions            = [];
        $selected = false;
        foreach($compositions_non_filter as $comp) {
            $args = array(
                'post_type' => ['sp_table', 'sp_tournament'],
                'tax_query' => [
                    'relation' => 'AND',
                    [
                        'taxonomy' => 'sp_season',
                        'field'    => 'id',
                        'terms'    => $curr_season_id,
                        'include_children' => false
                    ],
                    [
                        'taxonomy' => 'sp_league',
                        'field'    => 'id',
                        'terms'    => $comp['ligi']
                    ]
                ]
            );

            $posts = new WP_Query($args);
            if(!empty($posts->posts)){
                $compositions[] = $comp;
                if($selected_sostav == $comp['sostav']) {
                    $selected_sostav = $comp;
                    $selected = true;
                }
            }
        }
        if(!$selected) {
            $selected_sostav = $compositions[0];
        }

        // Получаем лиги находящиеся в данном составе
        $leagues = [];
        foreach($selected_sostav['ligi'] as $league_id){
            $args = array(
                'post_type'  => ['sp_table', 'sp_tournament'],
                'tax_query' => [
                [
                    'taxonomy' => 'sp_league',
                    'field' => 'id',
                    'terms' => $league_id
                ]
            ]
            );

            $posts = new WP_Query($args);
            if(!empty($posts->posts)){
                $leagues_id[] = get_term($league_id)->term_id;
                $leagues[] = get_term($league_id);
                if($curr_league_id == $league_id){
                    $curr_league = get_term($curr_league_id);
                }
            }
        }

        $leagues = array_reverse($leagues);

        if(!$curr_league) {
            $curr_league = $leagues[0];
        }

        $args = array(
            'posts_per_page' => 1,
            'post_type'      => ['sp_table', 'sp_tournament'],
            'orderby'        => 'meta_value_num',
            'order'          => 'DESC',
            'tax_query'      => [
                'relation' => 'AND',
                [
                    'taxonomy' => 'sp_season',
                    'field'    => 'id',
                    'terms'    => $curr_season_id
                ],
                [
                    'taxonomy' => 'sp_league',
                    'field'    => 'id',
                    'terms'    => $curr_league->term_id
                ]
            ]
        );

        $table_posts = new WP_Query($args);
        $table_id    = $table_posts->posts[0]->ID;

        if($table_posts->posts[0]->post_type == 'sp_table'){
            $table = new SP_League_Table($table_id);
            $colors = get_field('colors', $table_id);
        } else {
            $table = new SP_Tournament($table_id);
        }
        $table_data  = $table->data();
        unset($table_data[0]);

        $colors_html = '';

        foreach($colors as $color){
            $colors_html .= '<li class="block-data-with-select__head-info-item block-data-with-select__head-info-item--blue">
            <span class="circle-info" style="background-color:' . $color['color'] . '"></span>' . $color['title'] . '</li>';
        }


        $season_html = '
                <div class="block-data-with-select__select custom-select">
                  <select class="custom-select__select js-select js-select--leagues" id="season">
                  <option value="" placeholder>Выбор сезон</option>';
        foreach($seasons as $season):
            $season_html .= '<option value="' . $season->term_id . '"' . ($curr_season_id == $season->term_id ? 'selected' : ''). '>' . $season->name . '</option>';
        endforeach;

        $season_html .= '</select>
        </div>
                <div class="block-data-with-select__select custom-select">
                  <select class="custom-select__select js-select js-select--leagues" id="compositions">
                <option value="" placeholder>Выбор состава</option>';

        foreach($compositions as $composit):
            $season_html .= '<option value="' . $composit['sostav'] . '"' . ($selected_sostav['sostav'] == $composit['sostav'] ? 'selected': '' ). '>' . $composit['sostav'] . '</option>';
        endforeach;

        $season_html .= '
        </select>
        </div>
        <div class="block-data-with-select__select custom-select">
                  <select class="custom-select__select js-select js-select--leagues" id="league">
                    <option value="" placeholder>Выбор турнира</option>';
        foreach($leagues as $league):
            $season_html .= '<option value="' . $league->term_id . '"' . ($curr_league->term_id == $league->term_id ? 'selected' : '') . '>' . $league->name . '</option>';
        endforeach;
        $season_html .= '</select>
        </div>';

        $html = '';
        $i = 1;
        if($table_posts->posts[0]->post_type == 'sp_table'){
            $html = '<div class="block-data-with-select__content">
            <div class="table-cells table-cells--tournament table-cells--shadow">
              <table class="table-cells__table">
                <thead class="table-cells__head">
                  <tr>
                    <th class="table-cells__head-title table-cells__head-title--mini">#</th>
                    <th class="table-cells__head-title table-cells__head-title--mini">Команда</th>
                    <th class="table-cells__head-title table-cells__head-title--mini">Матчи</th>
                    <th class="table-cells__head-title table-cells__head-title--mini">Победы</th>
                    <th class="table-cells__head-title table-cells__head-title--mini">Ничьи</th>
                    <th class="table-cells__head-title table-cells__head-title--mini">Поражения</th>
                    <th class="table-cells__head-title table-cells__head-title--mini">Забито</th>
                    <th class="table-cells__head-title table-cells__head-title--mini">Пропущено</th>
                    <th class="table-cells__head-title table-cells__head-title--mini">Разница</th>
                    <th class="table-cells__head-title table-cells__head-title--mini">Очки</th>
                  </tr>
                </thead>
                <tbody class="table-cells__body">';
            foreach ($table_data as $key => $club) {
                $circle_color = 'rgba(0,0,0,0)';
                foreach($colors as $color) {
                    $rows = explode(',', $color['rows']);
                    if(in_array($i, $rows)) {
                        $circle_color = $color['color'];
                        break;
                    }
                }
                $abreviature = sp_team_abbreviation($key);
                $html .= '<tr class="table-cells__body-item">
                            <td class="tournament-table__text"><span class="circle-info" style="background-color: ' . $circle_color . '"></span>' . $club['pos'] . '</td>
                            <td>
                                <div class="command-with-logo">
                                    <div class="tournament-table__command-icon commands-icon command-with-logo__logo-wrap">' . sp_get_logo( $key, 'mini', array( 'itemprop' => 'url' ) ) . '</div>
                                    <div class="command-with-logo__text-wrap">
                                        <p class="tournament-table__command-name command-with-logo__name command-with-logo__name_desc">' . $club['name']  . '</p>
                                        <p class="tournament-table__command-name command-with-logo__name command-with-logo__name_mobile">' . $abreviature  . '</p>
                                    </div>
                                </div>
                            </td>
                            <td class="tournament-table__text">' . $club['p'] . '</td>
                            <td class="tournament-table__text">' . $club['w'] . '</td>
                            <td class="tournament-table__text">' . $club['d'] . '</td>
                            <td class="tournament-table__text">' . $club['l'] . '</td>
                            <td class="tournament-table__text">' . $club['f'] . '</td>
                            <td class="tournament-table__text">' . $club['a'] . '</td>
                            <td class="tournament-table__text">' . $club['gd'] . '</td>
                            <td class="tournament-table__text">' . round($club['pts']) . '</td>
                        </tr>';
                $i++;
            }
            $html .= '</tbody>
            </table>
          </div>
        </div>';
        } else {
            $lables = $table->labels;
            $data = $table->data('bracket', true);
            $events = $data[5];
            $html = '<section class="tournament-bracket-main">
            <div class="tournament-bracket-main__container">
            <div class="tournament-bracket-main__inner">
              <div class="tournament-bracket-main__head">
                <p class="tournament-bracket-main__title">Кубковая сетка</p>
              </div>
              <div class="tournament-bracket-main__content">
                <div class="tournament-bracket">';
            $i = 0;
            $line_count = [];
            foreach($lables as $lable){
                $i++;
                if($i == 1){
                    $line_count[$i]  =round(count($events) / 2);
                } else {
                    $line_count[$i] = $line_count[$i - 1] / 2;
                }
                $html .= '<div class="tournament-bracket__round">
                <p class="tournament-bracket__round-title">' . $lable . '</p>
                <ul class="tournament-bracket__list">';
                if($i == 1){
                    $j = 0;
                } else {
                    $j = count($events) - $line_count[$i - 1]  + 1;
                }
                for($j; $j < abs($line_count[$i] - count($events)) + 1 ; $j++){
                    $teams[0] = $events[$j]['teams'][0] != 0 ? new SP_Team($events[$j]['teams'][0]) : false;
                    $teams[1] = $events[$j]['teams'][1] != 0 ? new SP_Team($events[$j]['teams'][1]) : false;
                    $results = $events[$j]['results'];
                    $img[0] = get_the_post_thumbnail_url( $teams[0]->ID, 'full' );
                    $img[1] = get_the_post_thumbnail_url( $teams[1]->ID, 'full' );
                    $html .= '<li class="tournament-bracket__item tournament-bracket__item--winner">
                    <div class="tournament-bracket__match">
                        <div class="tournament-bracket__match-inner">
                        <div class="tournament-bracket__match-commands-icon-list commands-icon-list">
                            <div class="commands-icon-list__item commands-icon commands-icon--middle ' . ($results[0] > $results[1] ? 'commands-icon--winner' : '') . '"><img class="commands-icon__img" src="' . ($img[0] ? $img[0] : '/wp-content/uploads/2021/03/support-1-1.png') . '" alt=""></div>
                            <div class="commands-icon-list__item commands-icon commands-icon--middle ' . ($results[1] > $results[0] ? 'commands-icon--winner' : '') . '"><img class="commands-icon__img" src="' . ($img[1] ? $img[1] : '/wp-content/uploads/2021/03/support-1-1.png') . '" alt=""></div>
                        </div>
                        <div class="tournament-bracket__match-commands-name-count">
                            <div class="tournament-bracket__match-command ' . ($results[0] > $results[1] ? 'tournament-bracket__match-command--winner' : '') . '">
                            <p class="tournament-bracket__match-command-name">' . ($teams[0] ? $teams[0]->post->post_title : 'Название команды') . '</p>
                            <p class="tournament-bracket__match-command-count">' . ($results[0] ? $results[0] : 0) . '</p>
                            </div>
                            <div class="tournament-bracket__match-command ' . ($results[1] > $results[0] ? 'tournament-bracket__match-command--winner' : '') . '">
                            <p class="tournament-bracket__match-command-name">' . ($teams[1] ? $teams[1]->post->post_title : 'Название команды') . '</p>
                            <p class="tournament-bracket__match-command-count">' . ($results[1] ? $results[1] : 0) . '</p>
                            </div>
                        </div>
                        </div>
                    </div>
                    </li>';
                }
                $html .= '</ul>
                </div>';
            }
            $html .= '</div>
            </div>
            </div>
        </div>
        </section>';
        }


        $output_data = [
            'colors_html'     => $colors_html,
            'data_table'      => $html,
            'is_table'        => true,
            'selects_html'    => $season_html,
            'seasons'         => $seasons,
            'selected_season' => $curr_season_id,
            'leagues'         => $leagues,
            'selected_league' => $curr_league_id,
            'league_name'     => $curr_league->name,
            'compositions'    => $compositions,
            'selected_comp'   => $selected_sostav,
            'post_id'         => $post_id,
            'table_id'        => $table_id,
            'leagues_id'      => $leagues_id,
            'select_com_ligi' => $select_com['ligi'],
        ];
        wp_send_json($output_data);
    }
}

function wpb_custom_new_menu() {
    register_nav_menu('Главное меню',__( 'Main' ));
}
add_action( 'init', 'wpb_custom_new_menu' );

include get_template_directory() . '/menu-walkers.php';

add_filter( 'nav_menu_submenu_css_class', 'main_sub_menu_class', 10, 3 );
function main_sub_menu_class( $classes, $args, $depth ){
	$classes = ['sub-mega-menu__main-submenu'];
	return $classes;
}

add_action('wp_ajax_filterStatisticTable', 'filterStatisticTable');
add_action('wp_ajax_nopriv_filterStatisticTable', 'filterStatisticTable');

function filterStatisticTable() {
    if(!empty($_POST)){
        $url     = wp_get_referer();
        $post_id = url_to_postid( $url );
        $data = $_POST;

        $curr_season_id  = $data['season'];
        $curr_season = get_term($curr_season_id);
        $curr_league_id  = $data['league'];

        $selected_sostav = $data['sostav'];



          // Получение сезонов у которых есть таблицы
          // $seasons = get_terms_in_post('sp_season', 'sp_player');
          $seasons = [];
          $seasons_non_filter = get_terms(['taxonomy' => 'sp_season']);
          foreach($seasons_non_filter as $item) {
              $args = array(
                'posts_per_page' => -1,
                'post_type'  => 'sp_event',
                'post_status' => array('publish'),
                'tax_query' => [
                    [
                        'taxonomy' => 'sp_season',
                        'field' => 'id',
                        'terms' => $item,
                        'include_children' => false
                    ]
                ]
                );
                $sought = new WP_Query($args);
                if(!empty($sought->posts)) {
                    $seasons[] = $item;
                }
            }
            // Получаем все составы
            $compositions_non_filter = get_field('vybor_sostavov', $post_id);
            $compositions            = [];
            $selected = false;
            foreach($compositions_non_filter as $comp) {
                $args = array(
                    'posts_per_page' => -1,
                    'post_type'  => 'sp_event',
                    'post_status' => array('publish'),
                    'tax_query' => [
                        'relation' => 'AND',
                        [
                          'taxonomy' => 'sp_season',
                          'field' => 'id',
                          'terms' => [ $curr_season_id ],
                          'include_children' => false,
                        ],
                        [
                          'taxonomy' => 'sp_league',
                          'field' => 'id',
                          'terms' => $comp['ligi']
                        ]
                    ]
                );

                $posts = new WP_Query($args);
                if(!empty($posts->posts)){
                    $compositions[] = $comp;
                    if($selected_sostav == $comp['sostav']) {
                        $selected_sostav = $comp;
                        $selected = true;
                    }
                }
            }
            if(!$selected) {
                $selected_sostav = $compositions[0];
            }

            // Получаем лиги находящиеся в данном составе
            $leagues = [];
            foreach($selected_sostav['ligi'] as $league_id){
                $args = array(
                    'post_type' => 'sp_event',
                    'tax_query' => [
                    [
                        'taxonomy' => 'sp_league',
                        'field' => 'id',
                        'terms' => $league_id
                    ]
                ]
                );

                $posts = new WP_Query($args);
                if(count($posts->posts) > 1){
                    $leagues_id[] = get_term($league_id)->term_id;
                    $leagues[] = get_term($league_id);
                    if($curr_league_id == $league_id){
                        $curr_league = get_term($curr_league_id);
                    }
                }
            }
            $leagues = array_reverse($leagues);
            if(!$curr_league) {
                $curr_league = $leagues[0];
            }


        $season_html = '
                <div class="block-data-with-select__select custom-select">
                  <select class="custom-select__select js-select js-select--leagues" id="season">
                  <option value="" placeholder>Выбор сезон</option>';
        foreach($seasons as $season):
            $season_html .= '<option value="' . $season->term_id . '"' . ($curr_season_id == $season->term_id ? 'selected' : ''). '>' . $season->name . '</option>';
        endforeach;

        $season_html .= '</select>
        </div>
                <div class="block-data-with-select__select custom-select">
                  <select class="custom-select__select js-select js-select--leagues" id="compositions">
                <option value="" placeholder>Выбор состава</option>';
        foreach($compositions as $composit):
            $season_html .= '<option value="' . $composit['sostav'] . '"' . ($selected_sostav['sostav'] == $composit['sostav'] ? 'selected': '' ). '>' . $composit['sostav'] . '</option>';
        endforeach;

        $season_html .= '
        </select>
        </div>
        <div class="block-data-with-select__select custom-select">
                  <select class="custom-select__select js-select js-select--leagues" id="league">
                    <option value="" placeholder>Выбор турнира</option>';
        foreach($leagues as $league):
            $season_html .= '<option value="' . $league->term_id . '"' . ($curr_league->term_id == $league->term_id ? 'selected' : '') . '>' . $league->name . '</option>';
        endforeach;
        $season_html .= '</select>
        </div>';

        //Получение статистики игроков

        $args = array(
            'posts_per_page' => -1,
            'post_type'  => 'sp_player',
            'post_status' => array('publish'),
            'orderby'  => 'meta_value_num',
            'meta_key' => 'sp_number',
            'order'    => 'asc',
            'meta_query' => array(
                'relation' => 'OR',
                array(
                'key'     => 'sp_current_team',
                'value'   => [MAIN_TEAM_ID, YOUNG_TEAM_ID, YOUNG_TEAM3_ID],
                'compare' => 'IN'
                ),
                array(
                'key'     => 'sp_past_team',
                'value'   => [MAIN_TEAM_ID, YOUNG_TEAM_ID, YOUNG_TEAM3_ID],
                'compare' => 'IN'
                )
                ),
            'tax_query' => [
                'relation' => 'AND',
                [
                'taxonomy' => 'sp_season',
                'field' => 'id',
                'terms' => $curr_season_id
                ],
                [
                'taxonomy' => 'sp_league',
                'field' => 'id',
                'terms' => $curr_league->term_id
                ]
            ]
            );

        $players = new WP_Query( $args );
        $player_table_html = '';
        if(!empty($players->posts)):
            foreach($players->posts as $plr) {
                // dd(get_post_meta($plr->ID, 'sp_leagues'));
                $player_ID = $plr->ID;
                $playerObj = new SP_Player( $player_ID );
                $nationality = $playerObj->nationalities();
                $number = $playerObj->number;
                $name = explode( ' ', $plr->post_title);
                $pos = get_the_terms( $player_ID, 'sp_position');
                $parent = get_ancestors($pos[0]->term_id, 'sp_position');
                $short_pos = get_term_meta($pos[0]->term_id, 'sokrashhennoe_nazvanie', true );
                $short_parent_pos = get_term_meta($parent[0], 'sokrashhennoe_nazvanie', true );
                $link = get_permalink($player_ID);
                $fullTime = 0;
                $goals    = 0;
                $penalty  = 0;
                $assists  = 0;
                $matches  = 0;
                $ycard    = 0;
                $rcard    = 0;
                $fouls    = 0;
                $args = [
                  'post_type' => 'sp_calendar',
                  'posts_per_page' => -1
                ];
                $posts_calendar = get_posts($args);
                foreach($posts_calendar as $post){
                    $events = new SP_Calendar( $post->ID );
                    $events->status = 'publish';
                    $events->player = $player_ID;
                    $events->season = $curr_season_id;
                    $events->league = $curr_league->term_id;
                    $times = $events->data( );

                  foreach($times as $event) {
                        //    $game_time = get_post_meta( $event->ID, 'sp_minutes', true );
                          $match = new SP_Event($event->ID);
                          $timeline = $match->timeline(false, true);
                          unset($timeline[0]);
                          unset($timeline[1]);
                          $players = get_post_meta($event->ID, 'sp_players')[0];
                            $status = '';
                            foreach($playerObj->current_teams() as $team) {
                                if($players[$team][$player_ID]){
                                    $status = $players[$team][$player_ID]['status'];
                                }
                            }

                            $match_time = get_post_meta( $match->ID, 'sp_minutes', true );
                            if($match_time == "") {
                                $game_time = 90;
                            } else {
                                $game_time = $match_time;
                            }

                            if($status == 'sub') {
                                $match_time = 0;
                            } else {
                                $match_time = 90;
                            }
                            // $game_time = get_post_meta( $event->ID, 'sp_minutes', true );
                            foreach($timeline as $time_event){
                                if($time_event['id'] == $player_ID && $time_event['key'] == 'sub' ){
                                    $match_time = $game_time - $time_event['time'];
                                } else if ($time_event['sub'] == $player_ID && $time_event['key'] == 'sub') {
                                    $match_time = $time_event['time'];
                                }
                            }
                          $matches++;
                          $fullTime += (int)$match_time;
                  }
              }
                $leagues = $playerObj->leagues();
                foreach ($leagues as $league) {
                    if($league->term_id == $curr_league->term_id){
                      $stats = $playerObj->data( $league->term_id, false, -1 );
                      foreach ($stats as $stat) {
                        if ($stat['name'] === $curr_season->name) {
                          $goals += $stat['goals'];
                          $penalty += $stat['penalty'];
                          $assists += $stat['assists'];
                          // $matches += $stat['appearances'];
                          $ycard += $stat['yellowcards'];
                          $rcard += $stat['redcards'];
                          $fouls += $stat['fouls'];
                        }
                      }
                    }
                }
                if(get_the_terms($playerObj->ID, 'sp_season')[0]->term_id != $curr_season_id) continue;
            $players_ID[] = $playerObj->post->ID;
            $player_table_html .= '<tr class="table-cells__body-item">
                        <td class="tournament-table__text">' . $number . '</td>
                        <td>
                            <div class="leadership-list__country country-item">';
                                foreach ($nationality as $nat):
                                       $player_table_html .= '<div class="country-item__img-wrap">
                                            <img class="country-item__img" src="' . plugin_dir_url(SP_PLUGIN_FILE)
                                             . 'assets/images/flags/'. strtolower($nat) . '.png" alt=""></div>';
                                endforeach;
                                $player_table_html .= '<p class="country-item__name country-item__name--black"><a
                                href="' . $link . '" class="country-item__name--black"> ' . $name[0] . ' <span>' . $name[1] . '</span></a></p>
                            </div>
                        </td>
                        <td class="tournament-table__text">' . ($short_parent_pos != false ? $short_parent_pos : $short_pos) . '</td>
                        <td class="tournament-table__text">' . $matches . '</td>
                        <td class="tournament-table__text">' . ($fullTime ? $fullTime : 0) . '</td>
                        <td class="tournament-table__text">' . ($goals + $penalty) . '(' . $penalty . ')' . '</td>
                        <td class="tournament-table__text">' . $assists . '</td>
                        <td class="tournament-table__text">' . ($goals + $assists + $penalty) . '</td>
                        <td class="tournament-table__text">' . $ycard . '</td>
                        <td class="tournament-table__text">' . $rcard . '</td>
                    </tr>';
        }
    endif;

        $output_data = [
            'data_table'        => $player_table_html,
            'selects_html'      => $season_html,
            'seasons'           => $seasons,
            'selected_season'   => $curr_season_id,
            'leagues'           => $leagues,
            'selected_league'   => $curr_league_id,
            'league_name'       => 'Сезон ' . $curr_season->name,
            'compositions'      => $compositions,
            'selected_comp'     => $selected_sostav,
            'post_id'           => $post_id,
            'players_id'        => $players_ID,
            'leagues_id'        => $leagues_id,
        ];
        wp_send_json($output_data);
    }
}

add_action('wp_ajax_filterSeasonStatistic', 'filterSeasonStatistic');
add_action('wp_ajax_nopriv_filterSeasonStatistic', 'filterSeasonStatistic');

function filterSeasonStatistic() {
    if(!empty($_POST)){
        $data = $_POST;

        $curr_season_id = get_option( 'sportspress_season', null );
        $curr_season = get_term($curr_season_id);
        $curr_league_id = $data['league'];

        // Последние результаты
        $args = [
            'posts_per_page' => -1,
            'post_type' => 'sp_calendar',
            // 'tax_query' => [
            //     'relation' => 'AND',
            //     [
            //         'taxonomy' => 'sp_season',
            //         'field' => 'id',
            //         'terms' => $curr_season_id
            //         ],
            //         [
            //         'taxonomy' => 'sp_league',
            //         'field' => 'id',
            //         'terms' => $curr_league_id
            //         ]
            //     ]
        ];
        $posts_calendar = new WP_Query($args);
        if(!empty($posts_calendar->posts)){
            $calendar = new SP_Calendar( $posts_calendar->posts[0]->ID );
            $calendar->order = 'DESC';
            $calendar->status = 'publish';
            $calendar->league = $curr_league_id;
            $calendar->season = $curr_season_id;
            $data_calendar = $calendar->data();
            $usecolumns = $calendar->columns;
            $i = 0;
            $calendar_html = '';
            foreach($data_calendar as $event) {
                if($i >= 5) break;
                // if (get_the_terms($event->ID, 'sp_season')[0]->term_id == $curr_season_id && get_the_terms($event->ID, 'sp_league')[0]->term_id == $curr_league_id) {
                    $teams = get_post_meta( $event->ID, 'sp_team' );
                    $link = get_permalink($event->ID);
                    $main_results = apply_filters( 'sportspress_event_list_main_results', sp_get_main_results( $event ), $event->ID );
                    $date_html = get_post_time( 'd.m', false, $event );
                    $calendar_html .= '<li class="last-results__item last-results__item--slide swiper-slide">
                    <a class="link_hover_border" href="' . $link . '">
                    <div class="last-results__score-wrap result-score">
                        <div class="result-score__command-wrap">
                            <div class="result-score__command-icon commands-icon commands-icon--mini">' . sp_get_logo( $teams[0], 'mini', array( 'itemprop' => 'url' ) ) . '</div>
                            <p class="result-score__name">' . sp_team_abbreviation($teams[0]) . '</p>
                        </div>
                        <div class="result-score__result-date">
                            <div class="result-score__result"><span>' . ($main_results[0] ? $main_results[0] : 0) . '</span><span>&nbsp;:&nbsp;</span><span>' . ($main_results[1] ? $main_results[1] : 0) . '</span></div>
                            <p class="last-results__date result-score__date">' . $date_html . '</p>
                        </div>
                        <div class="result-score__command-wrap">
                            <div class="result-score__command-icon commands-icon commands-icon--mini">' .sp_get_logo( $teams[1], 'mini', array( 'itemprop' => 'url' ) ) . '</div>
                            <p class="result-score__name">' . sp_team_abbreviation($teams[1]) . '</p>
                        </div>
                    </div>
                    </a>
                </li>';
                $i++;
                // }
            }
        }else {
            $calendar_html = '<p class="season-statistics__item-title page-aside__element-title page-aside__element-title--scroll page-aside__element-title--black page-aside__element-title--align-center">Нет матчей</p>';
        }

        // Бомбардиры
        $args = array(
            'posts_per_page' => -1,
            'post_type'      => 'sp_player',
            'post_status'    => array('publish'),
            'orderby'        => 'meta_value_num',
            'order'          => 'asc',
            'meta_query'     => array(
                'relation' => 'OR',
                array(
                    'key'     => 'sp_team',
                    'value'   => MAIN_TEAM_ID,
                ),
                array(
                    'key'     => 'sp_team',
                    'value'   => YOUNG_TEAM_ID,
                ),
                array(
                    'key'     => 'sp_team',
                    'value'   => YOUNG_TEAM3_ID,
                ),
            ),
            'tax_query' => [
            'relation' => 'AND',
            [
                'taxonomy' => 'sp_season',
                'field'    => 'id',
                'terms'    => $curr_season_id
            ],
            [
                'taxonomy' => 'sp_league',
                'field'    => 'id',
                'terms'    => $curr_league_id
            ]
            ]
        );
        $players = new WP_Query( $args );
        $goals = 0;
        $bomb_html = '';
        if(!empty($players->posts)) {
            foreach($players->posts as $player){

                $player_ID = $player->ID;
                $playerObj = new SP_Player( $player_ID );
                $nationality = $playerObj->nationalities();
                $name = explode( ' ', $player->post_title);

                $img = get_the_post_thumbnail_url( $player_ID, 'full' );
                $link = get_permalink($player_ID);
                $leagues = $playerObj->leagues();
                foreach ($leagues as $league) {
                    if($league->term_id == $curr_league_id){
                        $stats = $playerObj->data( $league->term_id, false, -1 );
                        foreach ($stats as $stat) {
                            if ($stat['name'] === $curr_season->name || $stat['name'] === explode('-', $curr_season->name)[1]) {
                                $goals += $stat['goals'];
                                $goals += $stat['penalty'];
                            }
                        }
                    }
                }
                if($goals == 0) {continue;}
                $all_goals[$player->ID]['goals'] = $goals;
                $all_goals[$player->ID]['player'] = [
                    'id' => $player_ID,
                    'full_name' => $player->post_title,
                    'name' => $name,
                    'img' => $img,
                    'link' => $link
                ];
                $goals = 0;
            }

            $all_goals = wp_list_sort($all_goals, 'goals', 'DESC');
            $all_goals = array_slice($all_goals, 0, 5);
            foreach($all_goals as $item){
                $bomb_html .= '<li class="player-statistics__item">
                <a class="link_hover_border" href="' . $item['player']['link'] . '">
                <div class="player-statistics__item-wrap">
                    <div class="player-statistics__photo-wrap"><img class="player-statistics__photo" src="' .
                    $item['player']['img'] . '" alt="' . $item['player']['full_name'] . '"></div>
                    <div class="player-statistics__name-wrap">
                        <p class="player-statistics__first-name">' . $item['player']['name'][0] . '</p>
                        <p class="player-statistics__last-name">' . $item['player']['name'][1] . '</p>
                    </div>
                    <p class="player-statistics__amount">' . $item['goals'] . '</p>
                </div>
            </li>';
            }
        } else {
            $bomb_html = '<li><p class="season-statistics__item-title page-aside__element-title page-aside__element-title--scroll page-aside__element-title--black page-aside__element-title--align-center">Нет бомбардиров</p></li>';
        }

        // Турнирная таблица
        $od_teams = [MAIN_TEAM_ID, YOUNG_TEAM_ID, YOUNG_TEAM3_ID];
        $current_team_id = '';
        foreach($od_teams as $team) {
            $terms = get_the_terms($team, 'sp_league');
            foreach ($terms as  $term) {
                if($curr_league_id == $term->term_id && $team == MAIN_TEAM_ID) {
                    $current_team_id = MAIN_TEAM_ID;
                    break;
                } else if($curr_league_id == $term->term_id && $team == YOUNG_TEAM_ID) {
                    $current_team_id = YOUNG_TEAM_ID;
                    break;
                } else if($curr_league_id == $term->term_id && $team == YOUNG_TEAM3_ID) {
                    $current_team_id = YOUNG_TEAM3_ID;
                    break;
                }
            }
            if($current_team_id != '') break;
        }

        $args = array(
            'posts_per_page' => 1,
            'post_type'  => 'sp_table',
            'orderby'  => 'meta_value_num',
            'order'    => 'DESC',
            'tax_query' => [
            'relation' => 'AND',
            [
                'taxonomy' => 'sp_season',
                'field' => 'id',
                'terms' => [ $curr_season_id ]
            ],
            [
                'taxonomy' => 'sp_league',
                'field' => 'id',
                'terms' => $curr_league_id
            ]
            ]
        );

        $table_posts = new WP_Query($args);

        $table_id = $table_posts->posts[0]->ID;

        $curr_league = get_term($curr_league_id);
        if(!empty($table_posts->posts)){
            $table = new SP_League_Table($table_id);

            $table_dada = $table->data();
            unset($table_dada[0]);
            if(array_search($current_team_id, array_keys($table_dada)) <= 4) {
                $data = $data;
            }else if(array_search($current_team_id, array_keys($table_dada)) > 4 && array_search($current_team_id, array_keys($table_dada)) < count($table_dada) - 5) {
                $table_dada = array_slice($table_dada, array_search($current_team_id, array_keys($table_dada)) - 3, 8, true);
            } else if( array_search($current_team_id, array_keys($table_dada)) >= count($table_dada) - 5) {
                $table_dada = array_slice($table_dada, (array_search($current_team_id, array_keys($table_dada)) - 3), 8, true);
            } else {
                $table_dada = array_slice($table_dada, (array_search($current_team_id, array_keys($table_dada)) - 7), 8, true);
            }
            $table_dada = array_slice($table_dada, 0, 8, true);
            $i = 1;
            $table_html = '';
            foreach ($table_dada as $team_id => $team) {
                if ($team['name'] != 'Club') {
                    $img = get_the_post_thumbnail_url( $team_id, 'full' );
                    $link = get_permalink($team_id);
                    $table_html .= '
                    <li class="tournament-table__item tournament-table__item--main">
                            <p class="tournament-table__position tournament-table__text">' . $team['pos'] . '</p>
                            <div class="tournament-table__command-wrap command-with-logo">
                                <div class="tournament-table__command-icon' . (($team_id != $current_team_id) ? '_gray' : '') . ' commands-icon commands-icon--mini command-with-logo__logo-wrap"><img class="commands-icon__img command-with-logo__log" src="' . $img . '" alt=""></div>
                                <div class="command-with-logo__text-wrap">
                                    <p class="tournament-table__command-name command-with-logo__name">' . sp_team_abbreviation($team_id)  . '</p>
                                </div>
                            </div>
                            <div class="tournament-table__numbers">
                                <p class="tournament-table__wins tournament-table__text">' . $team['p'] . '</p>
                                <p class="tournament-table__moments tournament-table__text"><span>' . $team['f'] . '</span><span>&nbsp;-&nbsp;</span><span>' . $team['a']  . '</span></p>
                                <p class="tournament-table__defeat tournament-table__text">' . round($team['pts'])  . '</p>
                            </div>
                        </li>';
                }
                $i++;
            }
        } else {
            $table_html = '<p class="season-statistics__item-title page-aside__element-title page-aside__element-title--scroll page-aside__element-title--black page-aside__element-title--align-center">Нет результатов</p>';
        }

        $output_data = [
            'post' => $data,
            'calendar' => $posts_calendar->posts[0],
            'players' => $players->posts,
            'calendar_html' => $calendar_html,
            'calendar_data' => $data_calendar,
            'bomb_html' => $bomb_html,
            'table_html' => $table_html
        ];
        wp_send_json($output_data);
    }
}

add_shortcode('bombers', 'bombers_handler');
function bombers_handler() {
    $curr_season_id = get_option( 'sportspress_season', null );
    $curr_league_id = get_option( 'sportspress_league', null );
    $curr_season = get_term($curr_season_id)->name;
    $args = array(
        'posts_per_page' => -1,
        'post_type'      => 'sp_player',
        'post_status'    => array('publish'),
        'orderby'        => 'meta_value_num',
        'order'          => 'asc',
        'meta_query'     => array(
        array(
            'key'     => 'sp_team',
            'value'   => MAIN_TEAM_ID,
            'compare' => 'IN'
        )
        ),
        'tax_query' => [
        'relation' => 'AND',
        [
            'taxonomy' => 'sp_season',
            'field'    => 'id',
            'terms'    => $curr_season_id
        ],
        [
            'taxonomy' => 'sp_league',
            'field'    => 'id',
            'terms'    => $curr_league_id
        ]
        ]
    );
    $players = new WP_Query( $args );
    $goals = 0;

    foreach($players->posts as $player){

        $player_ID = $player->ID;
        $playerObj = new SP_Player( $player_ID );
        $nationality = $playerObj->nationalities();
        $name = explode( ' ', $player->post_title);
        $img = get_the_post_thumbnail_url( $player_ID, 'full' );
        // $times = $events->data( array('player' => $player_ID));

        $leagues = $playerObj->leagues();
        foreach ($leagues as $league) {
            if($league->term_id == $curr_league_id){
                $stats = $playerObj->data( $league->term_id, false, -1 );
                foreach ($stats as $stat) {
                    if ($stat['name'] === $curr_season) {
                        $goals += $stat['goals'] + $stat['penalty'];
                    }
                }
            }
        }
        if($goals == 0) {continue;}
        $all_goals[$player->ID]['goals'] = $goals;
        $all_goals[$player->ID]['player'] = [
            'id' => $player_ID,
            'full_name' => $player->post_title,
            'name' => $name,
            'img' => $img
        ];
        $goals = 0;
    }

    $all_goals = wp_list_sort($all_goals, 'goals', 'desc');
    $all_goals = array_slice($all_goals, 0, 5);
    ob_start();?>

    <ul class="player-statistics player-statistics--pb20 season-statistics__item-content">
    <?php
    foreach($all_goals as $item): ?>
        <li class="player-statistics__item">
            <div class="player-statistics__item-wrap">
                <div class="player-statistics__photo-wrap"><img class="player-statistics__photo" src="<?= $item['player']['img'] ?>" alt="<?= $item['player']['full_name'] ?>"></div>
                <div class="player-statistics__name-wrap">
                    <p class="player-statistics__first-name"><?= $item['player']['name'][0] ?></p>
                    <p class="player-statistics__last-name"><?= $item['player']['name'][1] ?></p>
                </div>
                <p class="player-statistics__amount"><?= $item['goals'] ?></p>
            </div>
        </li>
    <?php endforeach;?>
    </ul><a class="season-statistics__item-link btn btn--light-blue" href="/player-stats">К статистике игроков</a>
    <?php
    $content = ob_get_contents();
    ob_end_clean();
    return $content;
}

add_shortcode('last_results', 'last_results_handler');
function last_results_handler() {
    $curr_season_id = get_option( 'sportspress_season', null );
    $curr_league_id = get_option( 'sportspress_league', null );
    $curr_season = get_term($curr_season_id)->name;
    ob_start();
    ?>

    <ul class="last-results season-statistics__last-results-wrapper swiper-wrapper" id="last_result">
                        <?php
                        $args = [
                            'posts_per_page' => 1,
                            'post_type' => 'sp_calendar',
                            'tax_query' => [
                                'relation' => 'AND',
                                [
                                    'taxonomy' => 'sp_season',
                                    'field' => 'id',
                                    'terms' => $curr_season_id
                                    ],
                                    [
                                    'taxonomy' => 'sp_league',
                                    'field' => 'id',
                                    'terms' => $curr_league_id
                                    ]
                                ]
                        ];
                        $posts_calendar = new WP_Query($args);
                        $calendar = new SP_Calendar( $posts_calendar->posts[0]->ID );
                        $calendar->order = 'DESC';
                        $calendar->status = 'publish';
                        $data = $calendar->data();
                        $usecolumns = $calendar->columns;
                        $i = 0;

                	foreach($data as $event) {

                	    if ( $i < 5 && get_the_terms($event->ID, 'sp_season')[0]->term_id == $curr_season_id && get_the_terms($event->ID, 'sp_league')[0]->term_id == $curr_league_id) {
                    	    $teams = get_post_meta( $event->ID, 'sp_team' );
                    	    $main_results = apply_filters( 'sportspress_event_list_main_results', sp_get_main_results( $event ), $event->ID );
                    	    $date_html = get_post_time( 'd.m', false, $event );
                            ?>
                        <li class="last-results__item last-results__item--slide swiper-slide">
                            <div class="last-results__score-wrap result-score">
                                <div class="result-score__command-wrap">
                                    <div class="result-score__command-icon commands-icon commands-icon--mini"><?=sp_get_logo( $teams[0], 'mini', array( 'itemprop' => 'url' ) ) ?></div>
                                    <p class="result-score__name"><?= sp_team_abbreviation($teams[0]) ?></p>
                                </div>
                                <div class="result-score__result-date">
                                    <div class="result-score__result"><span><?=$main_results[0] ?></span><span>&nbsp;:&nbsp;</span><span><?=$main_results[1] ?></span></div>
                                    <p class="last-results__date result-score__date"><?=$date_html ?></p>
                                </div>
                                <div class="result-score__command-wrap">
                                    <div class="result-score__command-icon commands-icon commands-icon--mini"><?=sp_get_logo( $teams[1], 'mini', array( 'itemprop' => 'url' ) ) ?></div>
                                    <p class="result-score__name"><?= sp_team_abbreviation($teams[1]) ?></p>
                                </div>
                            </div>
                        </li>
                        <? }
                	$i++;} ?>
                    </ul><a class="season-statistics__item-link season-statistics__item-link--last-results btn btn--light-blue" href="#">Ко всем результатам</a>
    <?php
    $content = ob_get_contents();
    ob_end_clean();
    return $content;
}

add_shortcode('tournament', 'tournament_handler');
function tournament_handler() {
    global $post;
    $curr_season_id = get_option( 'sportspress_season', null );
    $curr_league_id = get_option( 'sportspress_league', null );

    $current_team_id = '';
    if($post->ID == 331 || $post->ID == 334 || $post->ID == 336) {
        $current_team_id = YOUNG_TEAM_ID;
        $curr_season_id = get_the_terms($current_team_id, 'sp_season')[0]->term_id;
        $curr_league_id = get_the_terms($current_team_id, 'sp_league')[0]->term_id;
    } else if($post->ID == 23871 || $post->ID == 23874 || $post->ID == 23878) {
        $current_team_id = YOUNG_TEAM3_ID;
        $curr_season_id = get_the_terms($current_team_id, 'sp_season')[0]->term_id;
        $curr_league_id = get_the_terms($current_team_id, 'sp_league')[0]->term_id;
    } else {
        $current_team_id = MAIN_TEAM_ID;
    }
    $args = array(
        'posts_per_page' => 1,
        'post_type'  => 'sp_table',
        'orderby'  => 'meta_value_num',
        'order'    => 'DESC',
        'tax_query' => [
            'relation' => 'AND',
            [
                'taxonomy' => 'sp_season',
                'field' => 'id',
                'terms' => $curr_season_id
            ],
            [
                'taxonomy' => 'sp_league',
                'field' => 'id',
                'terms' => $curr_league_id
            ]
        ]
    );

    $table_posts = new WP_Query($args);


    $table_id = $table_posts->posts[0]->ID;

    $curr_league = get_term($curr_league_id);

    $table = new SP_League_Table($table_id);


    $data = $table->data();

    unset($data[0]);
    if(array_search($current_team_id, array_keys($data)) <= 4) {
        $data = $data;
    }else if(array_search($current_team_id, array_keys($data)) > 4 && array_search($current_team_id, array_keys($data)) < count($data) - 5) {
        $data = array_slice($data, array_search($current_team_id, array_keys($data)) - 3, 8, true);
    } else if( array_search($current_team_id, array_keys($data)) >= count($data) - 5) {
        $data = array_slice($data, (array_search($current_team_id, array_keys($data)) - 3), 8, true);
    } else {
        $data = array_slice($data, (array_search($current_team_id, array_keys($data)) - 7), 8, true);
    }
    $data = array_slice($data, 0, 8, true);
    $i = 1;
    ob_start();
    ?>
    <ul class="tournament-table tournament-table--mini season-statistics__item-content tournament-table_mobile">
    <? foreach ($data as $key => $team) {
        if ($team['name'] != 'Club') {
            $img = get_the_post_thumbnail_url( $key, 'full' );
            ?>
    <li class="tournament-table__item tournament-table__item--main">
        <p class="tournament-table__position tournament-table__text"><?=$team['pos'] ?></p>
        <div class="tournament-table__command-wrap command-with-logo">
            <div class="tournament-table__command-icon<?= $key != MAIN_TEAM_ID || $key != YOUNG_TEAM_ID ||$key != YOUNG_TEAM3_ID  ? '_gray' : '' ?> commands-icon commands-icon--mini command-with-logo__logo-wrap"><img class="commands-icon__img command-with-logo__log" src="<?= $img ?>" alt=""></div>
            <div class="command-with-logo__text-wrap">
                <p class="tournament-table__command-name command-with-logo__name"><?= sp_team_abbreviation($key) ?></p>
            </div>
        </div>
        <div class="tournament-table__numbers tournament-table__numbers--mw-auto">
            <p class="tournament-table__wins tournament-table__text"><?=$team['p'] ?></p>
            <p class="tournament-table__moments tournament-table__text"><span><?=$team['f'] ?></span><span>&nbsp;|&nbsp;</span><span><?=$team['a'] ?></span></p>
            <p class="tournament-table__defeat tournament-table__text"><?= round($team['pts']) ?></p>
        </div>
    </li>
    <? }
    $i++;} ?>

    </ul><a class="season-statistics__item-link btn btn--light-blue" href="/tournament-table">К подробной таблице</a>
    <?php
    $content = ob_get_contents();
    ob_end_clean();
    return $content;
}

// Новости
add_shortcode('last_news', 'news_handler');
function news_handler($atts){
    $atrs = $rg = (object) shortcode_atts( [
		'category' => null
	], $atts );
    if($atrs->category) {
        $args = array(
            'posts_per_page' => 4,
            'orderby'     => 'date',
            'order'       => 'DESC',
            'post_type'   => 'news',
            'tax_query' => [
                [
                    'taxonomy' => 'news_categories',
                    'field' => 'id',
                    'terms' => $atrs->category
                ]
            ]
        );
        $pos_name_arr = get_term($atrs->category)->name;
        $pos_name = '';
        foreach($pos_name_arr as $str) {
            if(mb_substr(  $str, -1 , 1 ) == 'й') {
                $str = str_replace('й', 'е', $str);
            } else if(mb_substr(  $str, -1 , 1 ) == 'к') {
                $str .= 'и';
            } else if(mb_substr(  $str, -1 , 1 ) == 'ь') {
                $str = str_replace('ь', 'и', $str);
            }
            $pos_name .= ' ' . $str;
            $pos_name = strtolower($pos_name);
        }
    } else {
        $args = array(
            'posts_per_page' => 4,
            'orderby'     => 'date',
            'order'       => 'DESC',
            'post_type'   => 'news',
        );
    }

    $news = new WP_Query($args);
    if(empty($news->posts)) return "Нет новостей";
    ob_start()
    ?>
    <ul class="page-aside__latest-news-list">
        <?php foreach($news->posts as $item):
            $link = get_permalink($item->ID);
            $date = get_the_date('F j', $item->ID);
            $img = get_field('izobrazhenie', $item->ID) ? get_field('izobrazhenie', $item->ID)['sizes']['medium'] : get_the_post_thumbnail_url($item->ID);
            $category = get_the_terms($item->ID, 'news_categories')[0];
            ?>
        <li class="news-card news-card--latest-news page-aside__latest-news">
            <a class="news-card__link" href="<?= $link ?>"></a>
            <div class="news-card__img-wrap">
                <img class="news-card__img" src="<?= $img ? $img : get_template_directory_uri() . '/images/content/news_no_img.png' ?>" alt="<?= $item->post_title ?>">
            </div>
            <div class="news-card__content">
                <div class="news-card__category-date"><a class="news-card__category" href="<?= $link ?>"><?= $category->name ?></a>
                    <p class="news-card__date"><?= $date ?></p>
                </div>
                <p class="news-card__title"><?= convert_quotes_to_typographic($item->post_title) ?></p><a class="news-card__more more-details more-details--white" href="<?= $link ?>">Подробнее</a>
            </div>
        </li>
        <?php endforeach; ?>
    </ul>
    <?php if($atrs->category):
        $cat = get_term($atrs->category);
        ?>
        <a class="season-statistics__item-link btn btn--light-blue" href="/news<?= $cat->term_id ? '?cat=' . $cat->term_id : '' ?>">Ко всем новостям <?= getNewFormText($cat->name, 1) ?></a>
        <?php else: ?>
        <a class="season-statistics__item-link btn btn--light-blue" href="/news">Ко всем новостям</a>
    <?php endif; ?>
    <?php
    $content = ob_get_contents();
    ob_end_clean();
    return $content;
}

add_action( 'post_updated', 'add_date_to_post_title', 10, 3 );
function add_date_to_post_title( $post_ID, $post_after, $post_before ){
	$post = get_post($post_ID);
    if($post->post_type == 'sp_event'){
        remove_action('post_updated', 'add_date_to_post_title');
        $title = convert_quotes_to_typographic($post->post_title);
        $date = $post->post_date;
        $date = date('d.m.Y', strtotime($date));
        if(!preg_match('/([0-9]{2}.[0-9]{2}.[0-9]{4})$/', $title)){
            $my_post = array();
            $my_post['ID'] = $post_ID;
            $my_post['post_title'] = $title . ' ' . $date;
            wp_update_post( wp_slash($my_post) );
        }
    }
}

add_action('wp_ajax_filterMatchResults', 'filterMatchResults');
add_action('wp_ajax_nopriv_filterMatchResults', 'filterMatchResults');

function filterMatchResults() {
    if(!empty($_POST)){
        $url     = wp_get_referer();
        $post_id = url_to_postid( $url );
        $data = $_POST;
        global $wpdb;

        $curr_season_id  = $data['season'];
        $curr_league_id  = $data['league'];
        $selected_sostav = $data['sostav'];
        $curr_league = false;

        // Получение сезонов у которых есть таблицы
        $seasons = get_terms_in_post('sp_season', 'sp_event');

        // Получаем все составы
        $compositions_non_filter = get_field('vybor_sostavov', $post_id);
        $compositions            = [];
        $selected = false;
        foreach($compositions_non_filter as $comp) {
            $args = array(
                'post_type' => 'sp_event',
                'tax_query' => [
                    'relation' => 'AND',
                    [
                        'taxonomy' => 'sp_season',
                        'field'    => 'id',
                        'terms'    => $curr_season_id,
                        'include_children' => false
                    ],
                    [
                        'taxonomy' => 'sp_league',
                        'field'    => 'id',
                        'terms'    => $comp['ligi']
                    ]
                ],
            );

            $posts = new WP_Query($args);
            if(!empty($posts->posts)){
                $compositions[] = $comp;
                if($selected_sostav == $comp['sostav']) {
                    $selected_sostav = $comp;
                    $selected = true;
                }
            }
        }
        if(!$selected) {
            $selected_sostav = $compositions[0];
        }

        // Получаем лиги находящиеся в данном составе
        $leagues = [];
        foreach($selected_sostav['ligi'] as $league_id){
            $args = array(
                'post_type'  => 'sp_event',
                'tax_query' => [
                [
                    'taxonomy' => 'sp_league',
                    'field' => 'id',
                    'terms' => $league_id
                ]
            ]
            );

            $posts = new WP_Query($args);
            if(!empty($posts->posts)){
                $leagues_id[] = get_term($league_id)->term_id;
                $leagues[] = get_term($league_id);
                if($curr_league_id == $league_id){
                    $curr_league = get_term($curr_league_id);
                }
            }
        }
        $leagues = array_reverse($leagues);
        if(!$curr_league) {
            $curr_league = $leagues[0];
        }

        $season_html = '
                <div class="block-data-with-select__select custom-select">
                  <select class="custom-select__select js-select js-select--leagues" id="season">
                  <option value="" placeholder>Выбор сезон</option>';
        foreach($seasons as $season):
            $season_html .= '<option value="' . $season->term_id . '"' . ($curr_season_id == $season->term_id ? 'selected' : ''). '>' . $season->name . '</option>';
        endforeach;

        $season_html .= '</select>
        </div>
                <div class="block-data-with-select__select custom-select">
                  <select class="custom-select__select js-select js-select--leagues" id="compositions">
                <option value="" placeholder>Выбор состава</option>';
        foreach($compositions as $composit):
            $season_html .= '<option value="' . $composit['sostav'] . '"' . ($selected_sostav['sostav'] == $composit['sostav'] ? 'selected': '' ). '>' . $composit['sostav'] . '</option>';
        endforeach;

        $season_html .= '
        </select>
        </div>
        <div class="block-data-with-select__select custom-select">
                  <select class="custom-select__select js-select js-select--leagues" id="league">
                    <option value="" placeholder>Выбор турнира</option>';
        foreach($leagues as $league):
            $season_html .= '<option value="' . $league->term_id . '"' . ($curr_league->term_id == $league->term_id ? 'selected' : '') . '>' . $league->name . '</option>';
        endforeach;
        $season_html .= '</select>
        </div>';

        ob_start();
        $calendar = new SP_Calendar($post_id);
        $calendar->season = $curr_season_id;
        $calendar->league = $curr_league->term_id;
        $events = $calendar->data();


        if ( empty( $events ) ) {
            $in = 'AND 1 = 0'; // False logic to prevent SQL error
        } else {
            $event_ids = wp_list_pluck( $events, 'ID' );
            $in = 'AND ID IN (' . implode( ', ', $event_ids ) . ')';
        }

        // week_begins = 0 stands for Sunday
        $week_begins = intval(get_option('start_of_week'));

        $matches = array();

        $allDates = array();
        foreach($events as $key => $event) {

            if( in_array( array( 'year' => get_the_date('Y', $event->ID), 'month' => get_the_date('n', $event->ID) ), $allDates) ) continue;

            $allDates[$key]['year'] = get_the_date('Y', $event->ID);
            $allDates[$key]['month'] = get_the_date('n', $event->ID);
        }

        foreach($allDates as $date) {


        $year = $date['year'];
        $monthnum = $date['month'];

        $unixmonth = mktime(0, 0 , 0, $monthnum, 1, $year);
        $last_day = date('t', $unixmonth);

        $dayswithposts = $wpdb->get_results("SELECT DAYOFMONTH(post_date), ID
            FROM $wpdb->posts WHERE post_date >= '{$year}-{$monthnum}-01 00:00:00'
            AND post_type = 'sp_event' AND ( post_status = '{$_POST['type']}')
            $in
            AND post_date <= '{$year}-{$monthnum}-{$last_day} 23:59:59'", ARRAY_N);
        $wp_locale =  new WP_Locale();
        $key = $wp_locale->get_month($monthnum) . ' ' . $year;
        $matches[$key] = array();


        foreach($dayswithposts as $day) {
            array_push($matches[$key], array('day' => $day[0], 'eventId' => $day[1] ));
            }
        }
        $count = 0;
        if($data['type'] == 'publish'):
            $matches = array_reverse($matches);
                foreach($matches as $key => $match) {
                    $match = array_reverse($match);
                    if (empty($match)) continue;
                    $count++;
                    ?>
                    <div class="results-matches-table">
                    <p class="results-matches-table__title"><?=$key ?> </p>
                    <ul class="results-matches-table__list">
                        <?
                        foreach($match as $event) {
                            $eventObj = new SP_Event( $event['eventId'] );
                            //dd($eventObj);
                            $teams = get_post_meta($event['eventId'], 'sp_team');
                            $date = get_the_date('d.m', $event['eventId']);
                            $time = get_the_date('H:i', $event['eventId']);
                            $league = get_the_terms( $event['eventId'], 'sp_league');
                          //  $logo = wp_get_attachment_url(get_term_meta($league[0]->term_id, 'izobrazhenie', true)['sizes']['medium']);
                            $logo = get_field("izobrazhenie", "sp_league_".$league[0]->term_id)['sizes']['medium'];
                            //dd($logo);
                            $results = get_field('sp_results', $event['eventId']);
                            $main_results = [];
                            foreach($results as $key => $res) {
                                $main_results[] = ($res['goals'] != "" && $res['goals'] > 0) ? $res['goals'] : '0';
                            }
                            if($teams[0] == MAIN_TEAM_ID || $teams[0] == YOUNG_TEAM_ID || $teams[0] == YOUNG_TEAM3_ID) {
                                $match_result_class = $main_results[1] > $main_results[0] ? 'defeat' : ( $main_results[0] > $main_results[1] ? 'win' : 'draw' );
                                $match_result = $main_results[1] > $main_results[0] ? 'П' : ( $main_results[0] > $main_results[1] ? 'В' : 'Н' );
                                $match_result_for_mobile = $main_results[1] > $main_results[0] ? 'defeat' : ( $main_results[0] > $main_results[1] ? 'win' : 'draw' );
                              } else {
                                $match_result_class = $main_results[0] > $main_results[1] ? 'defeat' : ( $main_results[1] > $main_results[0] ? 'win' : 'draw' );
                                $match_result = $main_results[0] > $main_results[1] ? 'П' : ( $main_results[1] > $main_results[0] ? 'В' : 'Н' );
                                $match_result_for_mobile = $main_results[0] > $main_results[1] ? 'defeat' : ( $main_results[1] > $main_results[0] ? 'win' : 'draw' );
                              }
                            ?>
                                <li class="results-matches-table__item">
                                <p class="results-matches-table__indicator-outcome-match indicator-outcome-match indicator-outcome-match--<?= $match_result_class ?>"><?=$match_result?></p>
                                    <div class="match-date-when">
                                        <p class="match-date-when__date"><?=$date ?></p>
                                        <p class="match-date-when__when"><?=$time ?></p>
                                    </div>
                                    <div class="command-with-logo results-matches-table__command-with-logo">
                                        <div class="results-matches-table__command-icon commands-icon command-with-logo__logo-wrap">
                                        <?php if ($logo): ?>
                                        <img class="commands-icon__img command-with-logo__log" src="<?= $logo ?>" alt="">
                                        <?php endif; ?>
                                        </div>
                                        <div class="command-with-logo__text-wrap">
                                        <p class="results-matches-table__command-name command-with-logo__name command-with-logo__name--fz13"><?=$league[0]->name ?></p>
                                        <p class="results-matches-table__tour command-with-logo__name command-with-logo__name--mini command-with-logo__name--light-gray"><?=get_field('etaptur', $eventObj->post->ID) ?></p>
                                        </div>
                                    </div>
                                    <div class="results-matches-table__result-score-wrap">
                                        <div class="result-score results-matches-table__result-score">
                                        <div class="result-score__command-wrap">
                                            <div class="result-score__command-icon commands-icon commands-icon--mini">
                                                <?=sp_get_logo( $teams[0], 'mini', array( 'itemprop' => 'url' ) ) ?>
                                            </div>
                                            <p class="result-score__name"><?=get_post_meta($teams[0], 'sp_abbreviation', true) ?></p>
                                        </div>
                                        <div class="result-score__result-date"><!-- Класс для проигрыша -->
                                        <div class="result-score__result result-score__result--<?= $match_result_for_mobile ?>"><span><?=$main_results[0] ?></span><span>&nbsp;:&nbsp;</span><span><?=$main_results[1] ?></span></div>
                                        </div>
                                        <div class="result-score__command-wrap">
                                            <div class="result-score__command-icon commands-icon commands-icon--mini">
                                                <?=sp_get_logo( $teams[1], 'mini', array( 'itemprop' => 'url' ) ) ?>
                                            </div>
                                            <p class="result-score__name"><?=get_post_meta($teams[1], 'sp_abbreviation', true) ?></p>
                                        </div>
                                        </div>
                                    </div>
                                    <div class="results-matches-table__item-wrap-video-with-more">
                                    <div class="results-matches-table__mini-video">
                                        <?php $args = [
                                            'post_per_page' => 1,
                                            'post_type' => 'club_tv',
                                            'meta_query' => [
                                                'relation' => 'AND',
                                                [
                                                    'key' => 'is_add_match_result',
                                                    'value' => true
                                                ],
                                                [
                                                    'key' => 'match',
                                                    'value' => $event['eventId']
                                                ]
                                            ]
                                        ];
                                        $videos = new WP_Query($args);
                                        $videos = $videos->posts;
                                        if(!empty($videos)):
                                            $i = 0;
                                            $id = $videos[0]->ID;
                                            $i++;
                                            $img = get_field('izobrazhenie', $id)['sizes']['medium'];
                                            $group = get_field('group', $id);
                                            ?>
                                            <?php if($group['type'] == 'link'):?>

                                                    <a class="mini-video-open__link " href=""  data-src="<?= $group['video_link'] . '?autoplay=1&mute=1&enablejsapi=1' ?>" data-title="<?= convert_quotes_to_typographic($videos[0]->post_title) ?>">
                                                    <img class="mini-video-open__img" src="<?= $img ?>" width="110" height="60" alt="">
                                                    </a>
                                                <?php else: ?>
                                                    <a class="mini-video-open__link" href=""  data-src="" >
                                                    <img class="mini-video-open__img" src="<?= $img ?>" width="110" height="60" alt="">
                                                    </a>
                                                <div class="iframe_video">
                                                    <video controls="controls" autoplay>
                                                        <source src="<?= $group['video'] ?>">
                                                    </video>
                                                </div>
                                                <div class="overlay"></div>
                                                <div class="close_video"><img src="/images/icon/close-modal.svg'"></div>
                                                <?php endif; ?>
                                        <?php else: ?>

                                            <!-- <a class="mini-video-open__link" href="" >
                                            <img class="mini-video-open__img" src="/images/content/mini-video-img.png" width="110" height="60" alt="">
                                            </a> -->
                                            <div class="mini-video-open__link"></div>
                                        <?php endif; ?>
                                        </div>
                                        <a class="results-matches-table__more-details more-details more-details--dark-blue more-details--circle" href="<?=get_the_permalink($event['eventId']) ?>">Матч-центр</a>
                                    </div>
                                </li>
                                <?php } ?>
                            </ul>
                            </div>
                        <?php }
        else:
            foreach($matches as $key => $match) {
                if (count($match) == 0) continue;
                $count++;
        ?>
            <div class="results-matches-table">
                <p class="results-matches-table__title"><?=$key ?> </p>
                <ul class="results-matches-table__list">
                <? foreach($match as $event) {
                  $eventObj = new SP_Event( $event['eventId'] );

                  $teams = get_post_meta($event['eventId'], 'sp_team');
                  $date = get_the_date('d.m', $event['eventId']);
                  $time = get_the_date('H:i', $event['eventId']);
                  $league = get_the_terms( $event['eventId'], 'sp_league');
                //   $logo = wp_get_attachment_url(get_term_meta($league[0]->term_id, 'izobrazhenie', true)['sizes']['medium']);
                    $logo = get_field("izobrazhenie", "sp_league_".$league[0]->term_id)['sizes']['medium'];
                  $main_results = apply_filters( 'sportspress_event_list_main_results', sp_get_main_results( $eventObj ), $event['eventId'] );
                  $venue = get_the_terms( $event['eventId'], 'sp_venue')[0]->name;
                  $match_type = get_field('tip_matcha', $event['eventId']) == 'Домашний' ? 'home' : 'away';
                  ?>
                  <li class="results-matches-table__item results-matches-table__item--future">
                    <div class="where-play where-play--<?= $match_type ?>"></div>
                    <div class="match-date-when">
                      <p class="match-date-when__date"><?=$date?></p>
                      <p class="match-date-when__when"><?=$time?></p>
                    </div>
                    <div class="command-with-logo results-matches-table__command-with-logo">
                    <?php if($match_type == 'home'): ?>
                      <div class="commands-icon command-with-logo__logo-wrap command-with-logo__logo-wrap_mw-mh"><?=sp_get_logo( $teams[1], 'mini', array( 'itemprop' => 'url' ) ) ?></div>
                      <?php else: ?>
                        <div class="commands-icon command-with-logo__logo-wrap command-with-logo__logo-wrap_mw-mh"><?=sp_get_logo( $teams[0], 'mini', array( 'itemprop' => 'url' ) ) ?></div>
                      <?php endif; ?>
                      <div class="command-with-logo__text-wrap">
                      <?php if($match_type == 'home'): ?>
                        <p class="command-with-logo__name"><?=sp_team_short_name($teams[1]) ?></p>
                        <?php else: ?>
                          <p class="command-with-logo__name"><?=sp_team_short_name($teams[0]) ?></p>
                        <?php endif; ?>
                        <p class="address-stadium"><?=$venue ?></p>
                      </div>
                    </div>
                    <div class="results-matches-table__league command-with-logo results-matches-table__command-with-logo">
                      <div class="commands-icon commands-icon--mini command-with-logo__logo-wrap command-with-logo__logo-wrap_mw-mh"><img class="commands-icon__img command-with-logo__log" src="<?=$logo?>" alt=""></div>
                      <div class="command-with-logo__text-wrap command-with-logo__text-wrap--flex">
                        <p class="command-with-logo__name command-with-logo__name--fz13"><?=$league[0]->name ?></p>
                        <p class="command-with-logo__name command-with-logo__name--mini command-with-logo__name--light-gray"><?=get_field('etaptur', $eventObj->post->ID) ?></p>
                      </div>
                    </div><a class="results-matches-table__btn btn btn--mw200 btn--white btn--border-middle-blue" href="<?= get_permalink($eventObj->post->ID) ?>"><span class="btn__text">В матч центр</span></a>
                  </li>
                  <?}?>
                </ul>
              </div>
        <? }
        endif;
        if($count <= 0) {
            ob_end_clean();
            $content = '<p class="results-matches-table__title">Нет матчей</p>';
        } else {
            $content = ob_get_contents();
            ob_end_clean();
        }

        $output_data = [
            'data_table'        => $content,
            'selects_html'      => $season_html,
            'seasons'           => $seasons,
            'selected_season'   => $curr_season_id,
            'leagues'           => $leagues,
            'selected_league'   => $curr_league_id,
            'league_name'       => 'Сезон ' . $curr_season->name,
            'compositions'      => $compositions,
            'selected_comp'     => $selected_sostav,
            'post_id'           => $post_id,
            'players_id'        => $players_ID,
            'leagues_id'        => $leagues_id,
        ];
        wp_send_json($output_data);
    }
}

add_filter('acf/fields/post_object/query/name=igrok', 'my_acf_fields_post_object_query', 10, 3);
add_filter('acf/fields/post_object/query/name=gol_ot_igroka', 'my_acf_fields_post_object_query', 10, 3);
add_filter('acf/fields/post_object/query/name=player', 'my_acf_fields_post_object_query', 10, 3);
function my_acf_fields_post_object_query( $args, $field, $post_id ) {
    $args['meta_query'] = [
        [
            'key' => 'sp_current_team',
            'value' => [MAIN_TEAM_ID, YOUNG_TEAM_ID, YOUNG_TEAM3_ID]
        ]
        ];
    return $args;
}

function wpb_load_widget() {
    register_widget( 'advertising_widget' );
}
add_action( 'widgets_init', 'wpb_load_widget' );


// Creating the widget
class advertising_widget extends WP_Widget {
    // The construct part
    function __construct() {
        parent::__construct(
            // Base ID of your widget
            'advertising_widget',
            // Widget name will appear in UI
            __('Реклама', 'ad'),
            // Widget description
            array( 'description' => __( 'Виджет для добовления рекламных блоков' ), )
            );
    }

    // Creating widget front-end
    public function widget( $args, $instance ) {
        $title = apply_filters( 'widget_title', $instance['title'] );
        $img = get_field('img', 'widget_' . $args['widget_id']);
        $title = get_field('title', 'widget_' . $args['widget_id']);
        $link = get_field('link', 'widget_' . $args['widget_id']);
        $text = get_field('text', 'widget_' . $args['widget_id']);
        $content = '<li class="tilt-cards__item tilt-card-shop tilt-card-shop--mini anim-item"><a class="tilt-card-shop__link tilt-card" href="' . $link . '" target="_blank">
		<div class="tilt-card-shop__bg-img-wrap">
			<div class="tilt-card-shop__bg-img" style="background-image: url(' . $img . ');"></div>
		</div>
		<div class="tilt-card-shop__border" style="border-color: #ABD8FF;"></div>
		<div class="tilt-card-shop__description">
			<div class="tilt-card-shop__description-inner">
				<h3 class="tilt-card-shop__title">' . $title . '</h3>
				<p class="tilt-card-shop__text">' . $text . '</p>
			</div>
		</div></a>
		<div class="tilt-card-shop__shadow"></div>
	</li>';
    echo $content;
    }

    public function form( $instance ) {
    }

    public function update( $new_instance, $old_instance ) {
        return $new_instance;
    }
}

add_action('admin_post_nopriv_subscribe', 'subscribe');
add_action('admin_post_subscribe', 'subscribe');
function subscribe(){
    if(!empty($_POST)) {
        $email = trim($_POST['mailing-email']);
        $email = sanitize_text_field( $email );
        $args = [
            'post_type'=> 'subscriber',
            'meta_query' => [
                [
                    'key' => 'email',
                    'value' => $email
                ]
            ]
        ];
        $posts = new WP_Query($args);
        if(empty($posts->posts)){
            $post_data = array(
                'post_type'     => 'subscriber',
                'post_title'    =>  $email,
                'post_status'   => 'publish',
                'post_author'   => 1,
            );
            $post_id = wp_insert_post( $post_data );
            update_field('email', $email, $post_id);
        }
        wp_redirect(home_url());
    }
}

function num2word($num, $words)
{
    $num = $num % 100;
    if ($num > 19) {
        $num = $num % 10;
    }
    switch ($num) {
        case 1: {
            return($words[0]);
        }
        case 2: case 3: case 4: {
            return($words[1]);
        }
        default: {
            return($words[2]);
        }
    }
}

// if ( function_exists( 'add_image_size' ) ) {
// 	add_image_size( 'medium-large', 350, 500 ); // 300 в ширину и без ограничения в высоту
// }


if (!function_exists('wp_body_open')) :

    /**
     * Fire the wp_body_open action.
     *
     * Added for backwards compatibility to support pre 5.2.0 WordPress versions.
     *
     */
    function wp_body_open() {
        /**
         * Triggered after the opening <body> tag.
         *
         */
        do_action('wp_body_open');
    }

endif;


//remove short description  in  admin-panel
add_action('admin_init', 'wph_hide_editor');

function wph_hide_editor() {
    remove_post_type_support('sp_event', 'editor');
    remove_post_type_support('sp_event', 'excerpt');
}

function remove_the_short_description() {
    remove_meta_box( 'postimagediv', 'sp_event', 'side');
    remove_meta_box( 'sp_specsdiv', 'sp_event', 'side');
    remove_meta_box( 'sp_videodiv', 'sp_event', 'side');
    remove_meta_box( 'sp_modediv', 'sp_event', 'side');
    remove_meta_box( 'sp_shortcodediv', 'sp_event', 'side');
}

add_action('add_meta_boxes', 'remove_the_short_description', 999);

apply_filters('the_content', 'the_content_filter', 99);
function the_content_filter($content) {
    $content = preg_replace( '/"([^"]*)"/', "«$1»", $content );
    return $content;
}

add_filter( 'the_title', 'suppress_if_blurb', 10, 2 );
function suppress_if_blurb( $title, $id = null ) {
	$title = preg_replace( '/"([^"]*)"/', "«$1»", $title );
    return $title;
}

function convert_quotes_to_typographic($content){
    $content = preg_replace( '/"([^"]*)"/', "«$1»", $content );
    return $content;
}

function getNewFormText($text, $numForm){
    $urlXml = "https://htmlweb.ru/json/service/inflect?inflect=".urlencode($text);
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $urlXml);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER,1);
    $returned = curl_exec($ch);
    curl_close ($ch);
    $vac = json_decode($returned);
    if($vac->items){
        $text = $vac->items[$numForm];
        return mb_strtolower($text);
    }
    return false;
}

function get_match($ID){
    $event          = new SP_Event($ID);
    $eventPost = $event->post;
    $nowTimeMsk = current_time("timestamp");

    // START: Собриаем массив полей матча
    $match = [];
    $match['ID']                  = $ID;
    $match['link']                = get_permalink($ID);
    $match['teams']               = get_post_meta($ID, 'sp_team');                                                 // Команды учавствующие в матче
    $match['teams_short_name'][0] = sp_team_short_name($match['teams'][0]);
    $match['teams_short_name'][1] = sp_team_short_name($match['teams'][1]);
    $match['teams_logo'][0]       = sp_get_logo($match['teams'][0], 'mini', ['itemprop' => 'url']);
    $match['teams_logo'][1]       = sp_get_logo($match['teams'][1], 'mini', ['itemprop' => 'url']);
    $match['teams_class'][0]      = $match['teams'][0] == MAIN_TEAM_ID ? ' match-card__command-name--home' : '';
    $match['teams_class'][1]      = $match['teams'][1] == MAIN_TEAM_ID ? ' match-card__command-name--home' : '';

    $match['result']          = apply_filters('sportspress_event_list_main_results', sp_get_main_results($event), $ID);                    // Результаты матча
    $match['result_class'][0] = $match['result'][1] < $match['result'][0] ? 'match-score-number--dark-blue match-score-number--win' : '';
    $match['result_class'][1] = $match['result'][0] < $match['result'][1] ? 'match-score-number--dark-blue match-score-number--win' : '';

    $match['league']      = get_the_terms($ID, 'sp_league');                                                           // Все лиги матча, чаще всего нужна только 1
    $match['league_logo'] = wp_get_attachment_url(get_term_meta($match['league'][0]->term_id, 'izobrazhenie', true));  // Лого лиги

    $match['status']        = get_field('status_matcha', $ID);                                                                                          // Идет, окончен, не начался и т.д.
    $match['venue']         = get_the_terms($ID, 'sp_venue')[0]->name;                                                                                  // Место проведения
    $match['event_status']  = $eventPost->post_status;                                                                                                  // Статус поста матча (Publis, future)
    $match['times']         = get_field('vremya_nachala_tajmov', $ID);                                                                                  // Поле с временем начала кажого тайма
    $match['part']          = get_field('chast_matcha', $ID);                                                                                           // Перерыв, пенальти
    $match['cart_bg']       = get_field('cart_img', $ID) ? get_field('cart_img', $ID)['sizes']['medium'] : '/images/content/player-card-head-bg.webp';  // Изображение в карточке матча на главной
    $match['date']          = $eventPost->post_date;                                                                                                    // Дата начала матча, может быть изменено временем начала первого тайма
    $match['max_auto_time'] = 110 * 60;                                                                                                                 // Максимальное время матча в автоматическом режиме
    $match['cart_photo']    = '';
    $match['cart_video']    = '';
    $match['type']          = get_field('tip_matcha', $ID);
    $match['btn_more']    = '';

    $timeMatch = $eventPost->post_date;
    if ($match['times']["vremya_1go_tajma"]) {
        $match['date'] = $match['times']["vremya_1go_tajma"];
    }
    $match['date_unix'] = strtotime($match['date']);    // Время начала матча в UNIX

    if ($match['part'] == "Перерыв после 2-го тайма" || $match['part'] == "1-й доп. тайм" || $match['part'] == "Перерыв после 1-го доп. тайма" || $match['part'] == "2-й доп. тайм" || $match['part'] == "Пенальти") {
        $match['max_auto_time'] = 155 * 60; // Максимальное время матча в авто режиме
    }


    /* START: Получение статуса матча (Будущий, Прошедший или Идущий) */
    if ($match['status'] == "Матч перенесен" || $match['status'] == "Матч отменен" || $match['status'] == "Задержка матча" || strtotime($match['date']) > $nowTimeMsk) {
        $match['event_status'] = "FUTURE";
    } else if ($nowTimeMsk - $match['date_unix'] <= $match['max_auto_time'] && $nowTimeMsk - $match['date_unix'] > 0) {
        $match['event_status'] = "MATCH_PLAYIND";
    }  else {
        $match['event_status'] = "COMPLETE";
    }

    if ($match['status'] == "Матч окончен") {
        $match['event_status'] = "COMPLETE";
    }
    /* END: Получение статуса матча (Будущий, Прошедший или Идущий) */


    if ($match['times']["vremya_2go_tajma"]) {
        $timeMach2Unix = strtotime($match['times']["vremya_2go_tajma"]);

        $match['secondTimeUnix'] = $nowTimeMsk - $timeMach2Unix;
    }


    if ($match['times']["vremya_1go_dop_tajma"]) {
        $timeMach1dopUnix = strtotime($match['times']["vremya_1go_dop_tajma"]);

        $match['oneDopTimeUnix'] = $nowTimeMsk - $timeMach1dopUnix;
    }

    // START: Вывод значка фото и видео в карточке матча на главной
    $cart_gallery = get_posts([
        'post_type' => 'photogallery',
        'posts_per_page' => 1,
        'orderby' => 'date',
        'order' => 'DESC',
        'meta_query' => [
            [
                'key' => 'match',
                'value' => $ID,
                'compare' => 'LIKE',
            ],
        ],
    ]);
    if (!empty($cart_gallery)){
        $templ_dir = get_template_directory_uri();
        $match['cart_photo'] = '<span class="match-card__record-link" href="">
        <img src="' . $templ_dir . '/images/icon/photo.svg" alt="Фото матча">
    </span>';
    }

    $args = [
        'post_per_page' => 1,
        'post_type' => 'club_tv',
        'meta_query' => [
            'relation' => 'AND',
            [
                'key' => 'is_add_match_result',
                'value' => true,
            ],
            [
                'key' => 'match',
                'value' => $ID,
            ],
        ],
    ];
    $videos = new WP_Query($args);
    if (!empty($videos->posts)){
        $link = get_permalink($videos->posts[0]->ID);
        $templ_dir = get_template_directory_uri();
        $match['cart_video'] = '<a href="' . $link . '" class="match-card__record-link" ><img
        src="' . $templ_dir . '/images/icon/video.svg"
        alt="Видеозапись матча"></a>';
    }
    // END: Вывод значка фото и видео в карточке матча на главной

    // START: Информация для болельщиков в карточке
    if ($match['type'] != 'Домашний') {
        $inf_for_fun_link = get_permalink(17009);
        $args = [
            'posts_per_page' => 1,
            'post_type' => 'news',
            'tax_query' => [
                [
                    'taxonomy' => 'news_categories',
                    'field' => 'id',
                    'terms' => 291,
                ],
            ],
            'meta_query' => [
                'relation' => 'AND',
                [
                    'key' => 'privyazat_k_matchu',
                    'value' => true
                ],
                [
                    'key' => 'match',
                    'value' => $ID
                ]
            ]
        ];
        $new = get_posts($args);
        if(!empty($new)){
            $link = get_permalink($new[0]->ID);
            $match['btn_more'] = '<a class="btn btn--white btn--border-middle-blue" href="' . $link . '"><span class="btn__text">Информация о билетах</span></a>';
        }
    } else {
        $buy_ticket_link = get_field('buy_ticket', $ID);
        if($buy_ticket_link){
            $match['btn_more'] = '<a class="btn btn--white btn--border-middle-blue" href="' .  $buy_ticket_link . '"><span class="btn__text">Купить билет</span></a>';
        }
    }
    if($match['btn_more'] == '') {
        $match['btn_more'] = '<a class="news-card__more more-details" href="' . $match['link'] . '">Подробнее</a>';
    }
    // END: Информация для болельщиков в карточке

    // START: Просчет времение матча в автоматическом режиме
    if($match['event_status'] == "MATCH_PLAYIND"){
        $max_time = 90;
        switch ($match['status']) {
            case 'Матч не начался':
                $match['status'] = 'Матч не начался';
                $mode = "auto";

                $timeFromStart = $nowTimeMsk - $match['date_unix'];
                if ($timeFromStart <= 2820) {
                    $match['part'] = "Первый тайм";
                    $match['timer_active'] = true;
                    $timeClearMatch = $timeFromStart;
                } else if ($timeFromStart > 2820 && $timeFromStart <= 3720) {
                    $match['part'] = "Перерыв";
                    // $match['minute_past'] = "45";
                    // $match['second_past'] = "00";
                    $match['timer_active'] = false;
                    $timeClearMatch = 2700;
                } else if ($timeFromStart > 3720 && $timeFromStart <= 6540) {
                    $match['part'] = "Второй тайм";
                    $timeClearMatch = $timeFromStart - 1020;
                    $match['timer_active'] = true;

                } else {
                    $match['part'] = false;
                    $match['timer_active'] = false;
                    $timeClearMatch = $timeFromStart - 1020;
                }
                //  dd($timeFromStart);

                $match['status'] = 'Матч идет'; // code...


                $timePast = strval($timeClearMatch / 60);


                $match['minute_past'] = str_pad(explode(".", $timePast)[0], 2, "0", STR_PAD_LEFT);
                $match['second_past'] = str_pad($timeClearMatch - $match['minute_past'] * 60, 2, "0", STR_PAD_LEFT);

                $match['width_icon'] = $timePast / 90 * 100;
                //  dd($match['part']);
                // nowTimeMsk
                break;
            case 'Задержка матча':
                $match['status'] = 'Задержка матча'; // code...
                break;
            case 'Матч идет':
                $match['status'] = 'Матч идет'; // code...


                $timeFromStart = $nowTimeMsk - $match['date_unix'];

                // Проверка если забудут выкл 1 тайм
                if ($match['part'] == "1-й тайм") {
                    if ($timeFromStart <= 2940) {
                        $match['part'] = "Первый тайм";
                        $timeClearMatch = $timeFromStart;
                        $match['timer_active'] = true;
                    } else if ($timeFromStart > 2940 && $timeFromStart <= 3840) {
                        $match['part'] = "Перерыв";
                        $timeClearMatch = 2700;
                        $match['timer_active'] = false;
                    } else if ($timeFromStart > 3840 && $timeFromStart <= 6780) {
                        $match['part'] = "Второй тайм";
                        $match['timer_active'] = true;
                        $timeClearMatch = $timeFromStart - 1140;
                    }
                }
                // Проверка если забудут выкл 1 тайм

                if ($match['part'] == "Перерыв после 1-го тайма") {
                    $match['part'] = "Перерыв";
                    $timeClearMatch = 2700;
                    $match['timer_active'] = false;
                }

                // Проверка если забудут выкл 2 тайм
                if ($match['part'] == "2-й тайм") {

                    if ($secondTimeUnix) {
                        if ($secondTimeUnix < 0) {
                            $match['part'] = "Перерыв";
                            $timeClearMatch = 2700;
                            $match['timer_active'] = false;
                        } else {
                            $timeClearMatch = $secondTimeUnix + 2700; // + 45 минут
                            $match['timer_active'] = true;
                        }
                    } else {
                        // Если не поставили время второго тайма, то считаем от первого
                        if ($timeFromStart <= 2940) {
                            $match['part'] = "Первый тайм";
                            $timeClearMatch = $timeFromStart;
                            $match['timer_active'] = true;
                        }
                        if ($timeFromStart > 2940 && $timeFromStart <= 3840) {
                            $match['part'] = "Перерыв";
                            $timeClearMatch = 2700;
                            $match['timer_active'] = false;
                        } else if ($timeFromStart > 3840 && $timeFromStart <= 6780) {
                            $match['part'] = "Второй тайм";
                            $match['timer_active'] = true;
                            $timeClearMatch = $timeFromStart - 1140;
                        }
                        // Если не поставили время второго тайма
                    }


                    if ($timeFromStart > 6780) {
                        $match['part'] = "Матч окончен";
                        $timeClearMatch = $timeFromStart - 1140;
                        $match['timer_active'] = false;
                    }
                }


                if ($match['part'] == "Перерыв после 2-го тайма") {
                    $match['part'] = "Перерыв";
                    $timeClearMatch = 5400;
                    $match['timer_active'] = false;
                    //  $max_time = 120;
                }

                if ($match['part'] == "1-й доп. тайм") {

                    $max_time = 120;
                    if ($oneDopTimeUnix) {
                        if ($oneDopTimeUnix < 0) {
                            $match['part'] = "Перерыв";
                            $timeClearMatch = 5400;
                            $match['timer_active'] = false;
                        } else {
                            $timeClearMatch = $oneDopTimeUnix + 5400; // + 45 минут
                            $match['timer_active'] = true;
                        }
                    } else {
                        // Если не поставили время  тайма

                        if ($timeFromStart > 7680 && $timeFromStart <= 8580) {
                            $match['part'] = "Перерыв";
                            $timeClearMatch = 6300;
                            $match['timer_active'] = false;
                        } else if ($timeFromStart > 8580 && $timeFromStart <= 9480) {
                            $match['part'] = "Второй доп. тайм";
                            $timeClearMatch = $timeFromStart - 1140;
                            $match['timer_active'] = true;
                        }
                        // Если не поставили время второго тайма
                    }


                    if ($timeFromStart > 6780) {
                        $match['part'] = "1-й доп. тайм";
                        $timeClearMatch = 5400;
                    }


                    //  $max_time = 120;
                }
                // Проверка если забудут выкл 2 тайм
                $match['status'] = 'Матч идет'; // code...


                $timePast = strval($timeClearMatch / 60);

                $match['minute_past'] = str_pad(explode(".", $timePast)[0], 2, "0", STR_PAD_LEFT);
                $match['second_past'] = str_pad($timeClearMatch - $match['minute_past'] * 60, 2, "0", STR_PAD_LEFT);

                $match['width_icon'] = $timePast / $max_time * 100;

                //  dd($timePast);


                if ($match['part'] == "Перерыв после 1-го тайма" || $match['part'] == "Перерыв после 2-го тайма") $match['part'] = "Перерыв";


                break;
            case 'Матч окончен':
                $match['status'] = 'Матч окончен'; // code...
                $match['minute_past'] = "90";
                $match['second_past'] = "00";
                $match['width_icon'] = "100";
                $match['part'] = "Матч окончен";
                break;
            case 'Матч отменен':
                $match['status'] = 'Матч отменен'; // code...
                break;
            case 'Технический результат':
                $match['status'] = 'Технический результат'; // code...
                break;
            case 'Матч перенесен':
                $match['status'] = 'Матч перенесен'; // code...
                break;
            default:
                // code...
                break;
        }
        $match['width_icon'] = $match['width_icon'];
        $match['minute_past'] = $match['minute_past'];
        $match['second_past'] = $match['second_past'];
    }
    // END: Просчет времение матча в автоматическом режиме

    // END: Собриаем массив полей матча
    return (object)$match;
}