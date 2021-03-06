<?php

namespace GitHub;

use FileFetcher\FileFetcher;
use Michelf\Markdown;
use ParamProcessor\ProcessingResult;
use Parser;
use ParserHooks\HookHandler;
use GeSHi

/**
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
class GitHubParserHook implements HookHandler {

	private $fileFetcher;
	private $gitHubUrl;

	private $fileName;
	private $repoName;
	private $branchName;

	/**
	 * @param FileFetcher $fileFetcher
	 * @param string $gitHubUrl
	 */
	public function __construct( FileFetcher $fileFetcher, $gitHubUrl ) {
		$this->fileFetcher = $fileFetcher;
		$this->gitHubUrl = $gitHubUrl;
	}

	public function handle( Parser $parser, ProcessingResult $result ) {
		$this->setFields( $result );

		return $this->getRenderedContent();
	}

	private function setFields( ProcessingResult $result ) {
		$params = $result->getParameters();

		$this->fileName = $params['file']->getValue();
		$this->repoName = $params['repo']->getValue();
		$this->branchName = $params['branch']->getValue();
	}

	private function getRenderedContent() {
		$content = $this->getFileContent();

		if ( $this->isMarkdownFile() ) {
			$content = $this->renderAsMarkdown( $content );
        } else {
            $geshi = new GeSHi;
            $lang = $geshi->get_language_name_from_extension( pathinfo( $this->fileName, PATHINFO_EXTENSION ) );
            if ( strlen($lang) > 0 ) {
                    $geshi = new GeSHi( $content, $lang );
                    if( $geshi->error() == GESHI_ERROR_NO_SUCH_LANG )
                            return $geshi->error();
                    $geshi->set_encoding( 'UTF-8' );
                    $geshi->enable_classes(false);
                    #$geshi->set_overall_class( "source-$lang" );
                    $geshi->enable_keyword_links( false );
                    $content = $geshi->parse_code();
                    if ( $geshi->error() ) {
                            return 'error parsing file: ' . $geshi->error();
                    }
            }
        }
		return $content;
	}

	private function getFileContent() {
		return $this->fileFetcher->fetchFile( $this->getFileUrl() );
	}

	private function getFileUrl() {
		return sprintf(
			'%s/%s/%s/%s',
			$this->gitHubUrl,
			$this->repoName,
			$this->branchName,
			$this->fileName
		);
	}

	private function isMarkdownFile() {
		return $this->fileHasExtension( 'md' ) || $this->fileHasExtension( 'markdown' );
	}

	private function fileHasExtension( $extension ) {
		$fullExtension = '.' . $extension;
		return substr( $this->fileName, -strlen( $fullExtension ) ) === $fullExtension;
	}

	private function renderAsMarkdown( $content ) {
		return Markdown::defaultTransform( $content );
	}

}
