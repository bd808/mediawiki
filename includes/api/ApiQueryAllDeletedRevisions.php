<?php
/**
 * Created on Oct 3, 2014
 *
 * Copyright © 2014 Brad Jorsch "bjorsch@wikimedia.org"
 *
 * Heavily based on ApiQueryDeletedrevs,
 * Copyright © 2007 Roan Kattouw "<Firstname>.<Lastname>@gmail.com"
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License along
 * with this program; if not, write to the Free Software Foundation, Inc.,
 * 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301, USA.
 * http://www.gnu.org/copyleft/gpl.html
 *
 * @file
 */

/**
 * Query module to enumerate all deleted revisions.
 *
 * @ingroup API
 */
class ApiQueryAllDeletedRevisions extends ApiQueryRevisionsBase {

	public function __construct( ApiQuery $query, $moduleName ) {
		parent::__construct( $query, $moduleName, 'adr' );
	}

	/**
	 * @param ApiPageSet $resultPageSet
	 * @return void
	 */
	protected function run( ApiPageSet $resultPageSet = null ) {
		$user = $this->getUser();
		// Before doing anything at all, let's check permissions
		if ( !$user->isAllowed( 'deletedhistory' ) ) {
			$this->dieUsage(
				'You don\'t have permission to view deleted revision information',
				'permissiondenied'
			);
		}

		$db = $this->getDB();
		$params = $this->extractRequestParams( false );

		$result = $this->getResult();
		$pageSet = $this->getPageSet();
		$titles = $pageSet->getTitles();

		// This module operates in two modes:
		// 'user': List deleted revs by a certain user
		// 'all': List all deleted revs in NS
		$mode = 'all';
		if ( !is_null( $params['user'] ) ) {
			$mode = 'user';
		}

		if ( $mode == 'user' ) {
			foreach ( array( 'from', 'to', 'prefix', 'excludeuser' ) as $param ) {
				if ( !is_null( $params[$param] ) ) {
					$p = $this->getModulePrefix();
					$this->dieUsage( "The '{$p}{$param}' parameter cannot be used with '{$p}user'",
						'badparams' );
				}
			}
		} else {
			foreach ( array( 'start', 'end' ) as $param ) {
				if ( !is_null( $params[$param] ) ) {
					$p = $this->getModulePrefix();
					$this->dieUsage( "The '{$p}{$param}' parameter may only be used with '{$p}user'",
						'badparams' );
				}
			}
		}

		$this->addTables( 'archive' );
		if ( $resultPageSet === null ) {
			$this->parseParameters( $params );
			$this->addFields( Revision::selectArchiveFields() );
			$this->addFields( array( 'ar_title', 'ar_namespace' ) );
		} else {
			$this->limit = $this->getParameter( 'limit' ) ?: 10;
			$this->addFields( array( 'ar_title', 'ar_namespace', 'ar_timestamp', 'ar_rev_id', 'ar_id' ) );
		}

		if ( $this->fld_tags ) {
			$this->addTables( 'tag_summary' );
			$this->addJoinConds(
				array( 'tag_summary' => array( 'LEFT JOIN', array( 'ar_rev_id=ts_rev_id' ) ) )
			);
			$this->addFields( 'ts_tags' );
		}

		if ( !is_null( $params['tag'] ) ) {
			$this->addTables( 'change_tag' );
			$this->addJoinConds(
				array( 'change_tag' => array( 'INNER JOIN', array( 'ar_rev_id=ct_rev_id' ) ) )
			);
			$this->addWhereFld( 'ct_tag', $params['tag'] );
		}

		if ( $this->fld_content || !is_null( $this->diffto ) || !is_null( $this->difftotext ) ) {
			// Modern MediaWiki has the content for deleted revs in the 'text'
			// table using fields old_text and old_flags. But revisions deleted
			// pre-1.5 store the content in the 'archive' table directly using
			// fields ar_text and ar_flags, and no corresponding 'text' row. So
			// we have to LEFT JOIN and fetch all four fields.
			$this->addTables( 'text' );
			$this->addJoinConds(
				array( 'text' => array( 'LEFT JOIN', array( 'ar_text_id=old_id' ) ) )
			);
			$this->addFields( array( 'ar_text', 'ar_flags', 'old_text', 'old_flags' ) );

			// This also means stricter restrictions
			if ( !$user->isAllowedAny( 'undelete', 'deletedtext' ) ) {
				$this->dieUsage(
					'You don\'t have permission to view deleted revision content',
					'permissiondenied'
				);
			}
		}

		$dir = $params['dir'];
		$miser_ns = null;

		if ( $mode == 'all' ) {
			if ( $params['namespace'] !== null ) {
				$this->addWhereFld( 'ar_namespace', $params['namespace'] );
			}

			$from = $params['from'] === null
				? null
				: $this->titlePartToKey( $params['from'], $params['namespace'] );
			$to = $params['to'] === null
				? null
				: $this->titlePartToKey( $params['to'], $params['namespace'] );
			$this->addWhereRange( 'ar_title', $dir, $from, $to );

			if ( isset( $params['prefix'] ) ) {
				$this->addWhere( 'ar_title' . $db->buildLike(
					$this->titlePartToKey( $params['prefix'], $params['namespace'] ),
					$db->anyString() ) );
			}
		} else {
			if ( $this->getConfig()->get( 'MiserMode' ) ) {
				$miser_ns = $params['namespace'];
			} else {
				$this->addWhereFld( 'ar_namespace', $params['namespace'] );
			}
			$this->addTimestampWhereRange( 'ar_timestamp', $dir, $params['start'], $params['end'] );
		}

		if ( !is_null( $params['user'] ) ) {
			$this->addWhereFld( 'ar_user_text', $params['user'] );
		} elseif ( !is_null( $params['excludeuser'] ) ) {
			$this->addWhere( 'ar_user_text != ' .
				$db->addQuotes( $params['excludeuser'] ) );
		}

		if ( !is_null( $params['user'] ) || !is_null( $params['excludeuser'] ) ) {
			// Paranoia: avoid brute force searches (bug 17342)
			// (shouldn't be able to get here without 'deletedhistory', but
			// check it again just in case)
			if ( !$user->isAllowed( 'deletedhistory' ) ) {
				$bitmask = Revision::DELETED_USER;
			} elseif ( !$user->isAllowedAny( 'suppressrevision', 'viewsuppressed' ) ) {
				$bitmask = Revision::DELETED_USER | Revision::DELETED_RESTRICTED;
			} else {
				$bitmask = 0;
			}
			if ( $bitmask ) {
				$this->addWhere( $db->bitAnd( 'ar_deleted', $bitmask ) . " != $bitmask" );
			}
		}

		if ( !is_null( $params['continue'] ) ) {
			$cont = explode( '|', $params['continue'] );
			$op = ( $dir == 'newer' ? '>' : '<' );
			if ( $mode == 'all' ) {
				$this->dieContinueUsageIf( count( $cont ) != 4 );
				$ns = intval( $cont[0] );
				$this->dieContinueUsageIf( strval( $ns ) !== $cont[0] );
				$title = $db->addQuotes( $cont[1] );
				$ts = $db->addQuotes( $db->timestamp( $cont[2] ) );
				$ar_id = (int)$cont[3];
				$this->dieContinueUsageIf( strval( $ar_id ) !== $cont[3] );
				$this->addWhere( "ar_namespace $op $ns OR " .
					"(ar_namespace = $ns AND " .
					"(ar_title $op $title OR " .
					"(ar_title = $title AND " .
					"(ar_timestamp $op $ts OR " .
					"(ar_timestamp = $ts AND " .
					"ar_id $op= $ar_id)))))" );
			} else {
				$this->dieContinueUsageIf( count( $cont ) != 2 );
				$ts = $db->addQuotes( $db->timestamp( $cont[0] ) );
				$ar_id = (int)$cont[1];
				$this->dieContinueUsageIf( strval( $ar_id ) !== $cont[1] );
				$this->addWhere( "ar_timestamp $op $ts OR " .
					"(ar_timestamp = $ts AND " .
					"ar_id $op= $ar_id)" );
			}
		}

		$this->addOption( 'LIMIT', $this->limit + 1 );

		$sort = ( $dir == 'newer' ? '' : ' DESC' );
		$orderby = array();
		if ( $mode == 'all' ) {
			// Targeting index name_title_timestamp
			if ( $params['namespace'] === null || count( array_unique( $params['namespace'] ) ) > 1 ) {
				$orderby[] = "ar_namespace $sort";
			}
			$orderby[] = "ar_title $sort";
			$orderby[] = "ar_timestamp $sort";
			$orderby[] = "ar_id $sort";
		} else {
			// Targeting index usertext_timestamp
			// 'user' is always constant.
			$orderby[] = "ar_timestamp $sort";
			$orderby[] = "ar_id $sort";
		}
		$this->addOption( 'ORDER BY', $orderby );

		$res = $this->select( __METHOD__ );
		$pageMap = array(); // Maps ns&title to array index
		$count = 0;
		$nextIndex = 0;
		$generated = array();
		foreach ( $res as $row ) {
			if ( ++$count > $this->limit ) {
				// We've had enough
				if ( $mode == 'all' ) {
					$this->setContinueEnumParameter( 'continue',
						"$row->ar_namespace|$row->ar_title|$row->ar_timestamp|$row->ar_id"
					);
				} else {
					$this->setContinueEnumParameter( 'continue', "$row->ar_timestamp|$row->ar_id" );
				}
				break;
			}

			// Miser mode namespace check
			if ( $miser_ns !== null && !in_array( $row->ar_namespace, $miser_ns ) ) {
				continue;
			}

			if ( $resultPageSet !== null ) {
				if ( $params['generatetitles'] ) {
					$key = "{$row->ar_namespace}:{$row->ar_title}";
					if ( !isset( $generated[$key] ) ) {
						$generated[$key] = Title::makeTitle( $row->ar_namespace, $row->ar_title );
					}
				} else {
					$generated[] = $row->ar_rev_id;
				}
			} else {
				$revision = Revision::newFromArchiveRow( $row );
				$rev = $this->extractRevisionInfo( $revision, $row );

				if ( !isset( $pageMap[$row->ar_namespace][$row->ar_title] ) ) {
					$index = $nextIndex++;
					$pageMap[$row->ar_namespace][$row->ar_title] = $index;
					$title = $revision->getTitle();
					$a = array(
						'pageid' => $title->getArticleID(),
						'revisions' => array( $rev ),
					);
					$result->setIndexedTagName( $a['revisions'], 'rev' );
					ApiQueryBase::addTitleInfo( $a, $title );
					$fit = $result->addValue( array( 'query', $this->getModuleName() ), $index, $a );
				} else {
					$index = $pageMap[$row->ar_namespace][$row->ar_title];
					$fit = $result->addValue(
						array( 'query', $this->getModuleName(), $index, 'revisions' ),
						null, $rev );
				}
				if ( !$fit ) {
					if ( $mode == 'all' ) {
						$this->setContinueEnumParameter( 'continue',
							"$row->ar_namespace|$row->ar_title|$row->ar_timestamp|$row->ar_id"
						);
					} else {
						$this->setContinueEnumParameter( 'continue', "$row->ar_timestamp|$row->ar_id" );
					}
					break;
				}
			}
		}

		if ( $resultPageSet !== null ) {
			if ( $params['generatetitles'] ) {
				$resultPageSet->populateFromTitles( $generated );
			} else {
				$resultPageSet->populateFromRevisionIDs( $generated );
			}
		} else {
			$result->setIndexedTagName_internal( array( 'query', $this->getModuleName() ), 'page' );
		}
	}

	public function getAllowedParams() {
		$ret = parent::getAllowedParams() + array(
			'user' => array(
				ApiBase::PARAM_TYPE => 'user'
			),
			'namespace' => array(
				ApiBase::PARAM_ISMULTI => true,
				ApiBase::PARAM_TYPE => 'namespace',
				ApiBase::PARAM_DFLT => null,
			),
			'start' => array(
				ApiBase::PARAM_TYPE => 'timestamp',
				ApiBase::PARAM_HELP_MSG_INFO => array( array( 'useronly' ) ),
			),
			'end' => array(
				ApiBase::PARAM_TYPE => 'timestamp',
				ApiBase::PARAM_HELP_MSG_INFO => array( array( 'useronly' ) ),
			),
			'dir' => array(
				ApiBase::PARAM_TYPE => array(
					'newer',
					'older'
				),
				ApiBase::PARAM_DFLT => 'older',
				ApiBase::PARAM_HELP_MSG => 'api-help-param-direction',
			),
			'from' => array(
				ApiBase::PARAM_HELP_MSG_INFO => array( array( 'nonuseronly' ) ),
			),
			'to' => array(
				ApiBase::PARAM_HELP_MSG_INFO => array( array( 'nonuseronly' ) ),
			),
			'prefix' => array(
				ApiBase::PARAM_HELP_MSG_INFO => array( array( 'nonuseronly' ) ),
			),
			'excludeuser' => array(
				ApiBase::PARAM_TYPE => 'user',
				ApiBase::PARAM_HELP_MSG_INFO => array( array( 'nonuseronly' ) ),
			),
			'tag' => null,
			'continue' => array(
				ApiBase::PARAM_HELP_MSG => 'api-help-param-continue',
			),
			'generatetitles' => array(
				ApiBase::PARAM_DFLT => false
			),
		);

		if ( $this->getConfig()->get( 'MiserMode' ) ) {
			$ret['user'][ApiBase::PARAM_HELP_MSG_APPEND] = array(
				'apihelp-query+alldeletedrevisions-param-miser-user-namespace',
			);
			$ret['namespace'][ApiBase::PARAM_HELP_MSG_APPEND] = array(
				'apihelp-query+alldeletedrevisions-param-miser-user-namespace',
			);
		}

		return $ret;
	}

	protected function getExamplesMessages() {
		return array(
			'action=query&list=alldeletedrevisions&adruser=Example&adrlimit=50'
				=> 'apihelp-query+alldeletedrevisions-example-user',
			'action=query&list=alldeletedrevisions&adrdir=newer&adrlimit=50'
				=> 'apihelp-query+alldeletedrevisions-example-ns-main',
		);
	}

	public function getHelpUrls() {
		return 'https://www.mediawiki.org/wiki/API:Alldeletedrevisions';
	}
}
