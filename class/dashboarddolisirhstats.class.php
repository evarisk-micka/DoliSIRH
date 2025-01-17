<?php
/* Copyright (C) 2022 EOXIA <dev@eoxia.com>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 */

/**
 *       \file       class/dashboarddolisirhstats.class.php
 *       \ingroup    dolisirh
 *       \brief      Fichier de la classe de gestion des stats du dashboard
 */

require_once DOL_DOCUMENT_ROOT . '/core/class/dolgraph.class.php';
require_once DOL_DOCUMENT_ROOT . '/core/class/html.form.class.php';

require_once __DIR__ . '/dolisirhstats.class.php';

/**
 *	Class to manage stats for dashboard
 */
class DashboardDoliSIRHStats extends DoliSIRHStats
{
	const DASHBOARD_TIMESPENT = 0;

	/**
	 * 	Constructor
	 *
	 * 	@param	DoliDB $db Database handler
	 */
	public function __construct($db)
	{
		$this->db = $db;
	}

	/**
	 * Load dashboard info.
	 *
	 * @param  int       $load_timespent Load dashboard timespent info
	 *
	 * @return array
	 * @throws Exception
	 */
	public function load_dashboard($load_timespent = 1)
	{
		$dolisirh_dashboard = DoliSIRHStats::load_dashboard();

		$dashboard_data = [
			'widgets' => [
				'timespent' => ($load_timespent) ? $dolisirh_dashboard['widgets'] : []
            ],
			'graphs' => [
				'timespent' => ($load_timespent) ? $dolisirh_dashboard['graphs'] : []
            ]
        ];

		return $dashboard_data;
	}

	/**
	 * Show dashboard.
	 *
	 * @return void
	 * @throws Exception
	 */
	public function show_dashboard($load_timespent = 1)
	{
		global $conf, $form, $langs, $user;

		$WIDTH  = DolGraph::getDefaultGraphSizeForStats('width');
		$HEIGHT = DolGraph::getDefaultGraphSizeForStats('height');

		$dashboard_data = $this->load_dashboard($load_timespent);

		print '<form method="POST" action="' . $_SERVER["PHP_SELF"] . '" class="dashboard" id="dashBoardForm">';
		print '<input type="hidden" name="token" value="' . newToken() . '">';
		print '<input type="hidden" name="action" value="view">';

		$disableWidgetList = json_decode($user->conf->DOLISIRH_DISABLED_DASHBOARD_INFO);

		if (!empty($dashboard_data['widgets'])) {
			foreach ($dashboard_data['widgets'] as $dashboardLine) {
				foreach ($dashboardLine as $key => $dashboardLinesingle) {
					if (isset($disableWidgetList->$key) && $disableWidgetList->$key == 0) {
						$dashboardLinesArray[$key] = $dashboardLinesingle['widgetName'];
					}
				}
			}
		}

		print '<div class="add-widget-box" style="' . (!empty((array)$disableWidgetList) ? '' : 'display:none') . '">';
		print Form::selectarray('boxcombo', $dashboardLinesArray, -1, $langs->trans("ChooseBoxToAdd") . '...', 0, 0, '', 1, 0, 0, 'DESC', 'maxwidth150onsmartphone hideonprint add-dashboard-widget', 0, 'hidden selected', 0, 1);
		if (!empty($conf->use_javascript_ajax)) {
			include_once DOL_DOCUMENT_ROOT . '/core/lib/ajax.lib.php';
			print ajax_combobox("boxcombo");
		}
		print '</div>';
		print '<div class="fichecenter">';

		if (!empty($dashboard_data['widgets'])) {
			$openedDashBoard = '';
			foreach ($dashboard_data['widgets'] as $dashboardLine) {
				foreach ($dashboardLine as $key => $dashboardLinesingle) {
					if (!isset($disableWidgetList->$key) && is_array($dashboardLinesingle) && !empty($dashboardLinesingle)) {
						$openedDashBoard .= '<div class="box-flex-item"><div class="box-flex-item-with-margin">';
						$openedDashBoard .= '<div class="info-box info-box-sm">';
						$openedDashBoard .= '<span class="info-box-icon">';
						$openedDashBoard .= '<i class="' . $dashboardLinesingle["picto"] . '"></i>';
						$openedDashBoard .= '</span>';
						$openedDashBoard .= '<div class="info-box-content">';
						$openedDashBoard .= '<div class="info-box-title" title="' . $langs->trans("Close") . '">';
						$openedDashBoard .= '<span class="close-dashboard-widget" data-widgetname="' . $key . '"><i class="fas fa-times"></i></span>';
						$openedDashBoard .= '</div>';
						$openedDashBoard .= '<div class="info-box-lines">';
						$openedDashBoard .= '<div class="info-box-line" style="font-size : 20px;">';
						for ($i = 0; $i < count($dashboardLinesingle['label']); $i++) {
							$openedDashBoard .= '<span class=""><strong>' . $dashboardLinesingle["label"][$i] . ' : ' . '</strong>';
							$openedDashBoard .= '<span class="classfortooltip badge badge-info" title="' . $dashboardLinesingle["label"][$i] . ' : ' . $dashboardLinesingle["content"][$i] . '" >' . $dashboardLinesingle["content"][$i] . '</span>';
							$openedDashBoard .= (!empty($dashboardLinesingle["tooltip"][$i]) ? $form->textwithpicto('', $langs->transnoentities($dashboardLinesingle["tooltip"][$i])) : '') . '</span>';
							$openedDashBoard .= '<br>';
						}
						$openedDashBoard .= '</div>';
						$openedDashBoard .= '</div><!-- /.info-box-lines --></div><!-- /.info-box-content -->';
						$openedDashBoard .= '</div><!-- /.info-box -->';
						$openedDashBoard .= '</div><!-- /.box-flex-item-with-margin -->';
						$openedDashBoard .= '</div>';
					}
				}
			}
			print '<div class="opened-dash-board-wrap"><div class="box-flex-container">' . $openedDashBoard . '</div></div>';
		}

		print '<div class="graph-dashboard wpeo-gridlayout grid-2">';

		if (is_array($dashboard_data['graphs']) && !empty($dashboard_data['graphs'])) {
			foreach ($dashboard_data['graphs'] as $keyelement => $datagraph) {
                if (is_array($datagraph) && !empty($datagraph)) {
                    foreach ($datagraph as $keyelement2 => $datagraph2) {
                        $nbdata = 0;
                        if (is_array($datagraph2['data']) && !empty($datagraph2['data'])) {
                            if ($datagraph2['dataset'] == 2) {
                                foreach ($datagraph2['data'] as $datagrapharray) {
                                    unset($datagrapharray[0]);
                                    foreach ($datagrapharray as $datagraphsingle) {
                                        if (!empty($datagraphsingle)) {
                                            $nbdata = 1;
                                        }
                                    }
                                }
                            } else {
                                foreach ($datagraph2['data'] as $datagraphsingle) {
                                    $nbdata += $datagraphsingle;
                                }
                            }
                            if ($nbdata > 0) {
                                $arraykeys = array_keys($datagraph2['data']);
                                foreach ($arraykeys as $key) {
                                    if ($datagraph2['dataset'] == 2) {
                                        $datalegend[$keyelement2][] = $langs->trans($datagraph2['labels'][$key]['label']);
                                        $data[$keyelement2][] = $datagraph2['data'][$key];
                                    } else {
                                        $data[$keyelement2][] = [
                                            0 => $langs->trans($datagraph2['labels'][$key]['label']),
                                            1 => $datagraph2['data'][$key]
                                        ];
                                    }

                                    $datacolor[$keyelement2][] = $langs->trans($datagraph2['labels'][$key]['color']);
                                }

                                $filename[$keyelement2] = $keyelement2 . '.png';
                                $fileurl[$keyelement2] = DOL_URL_ROOT . '/viewimage.php?modulepart=dolisirh&file=' . $keyelement2 . '.png';

                                $graph = new DolGraph();
                                $graph->SetData($data[$keyelement2]);
                                if ($datagraph2['dataset'] == 2) {
                                    $graph->SetLegend($datalegend[$keyelement2]);
                                }
                                $graph->SetDataColor($datacolor[$keyelement2]);
                                $graph->SetType([$datagraph2['type'] ?? 'pie']);
                                $graph->SetWidth($datagraph2['width'] ?? $WIDTH);
                                $graph->SetHeight($datagraph2['height'] ?? $HEIGHT);
                                $graph->setShowLegend(2);
                                $graph->draw($filename[$keyelement2], $fileurl[$keyelement2]);
                                print '<div>';
                                print load_fiche_titre($datagraph2['title'], $datagraph2['morehtmlright'], $datagraph2['picto']);
                                print $graph->show();
                                print '</div>';
                            }
                        }
                    }
                }
			}
		}

		print '</div></div></div>';
		print '</form>';
	}
}

