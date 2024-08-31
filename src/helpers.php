<?php

Use Sentiment\Analyzer;
use PhpScience\TextRank\Tool\StopWords\English;
use PhpScience\TextRank\TextRankFacade;

helper("ai");

if(helper_add("rank")){

	function rank():TextRankFacade{

		return new TextRankFacade();
	}
}

if(helper_add("analyzer")){

	function analyzer():Analyzer{

		return new Analyzer(); 
	}
}

if(helper_add("probe")){

	/**
	 * VADER (Valence Aware Dictionary and sEntiment Reasoner) 
	 * 
	 * is a lexicon and rule-based sentiment analysis tool that is 
	 * specifically attuned to sentiments expressed in social media. 
	 * It is fully open-sourced under the [MIT License] (we sincerely appreciate all 
	 * attributions and readily accept most contributions, but please don't hold us liable).
	 */
	function probe(string $text):array{

		return analyzer()->getSentiment($text);
	}
}

if(helper_add("tags")){

	function tags(string $text):array{

		$api = rank();
		$api->setStopWords(new English);

		return $api->getOnlyKeyWords($text); 
	}
}

if(helper_add("summary")){

	function summary(string $text):array{

		$api = rank();
		$api->setStopWords(new English);

		return $api->summarizeTextBasic($text); 
	}
}

if(helper_add("highlights")){

	function highlights(string $text){

		$api = rank();
		$api->setStopWords(new English);

		return $api->getHighlights($text); 
	}
}