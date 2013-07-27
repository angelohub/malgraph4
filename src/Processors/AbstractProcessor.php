<?php
abstract class AbstractProcessor
{
	public abstract function getSubProcessors();

	public function beforeProcessing(&$context)
	{
	}

	public function afterProcessing(&$context)
	{
	}

	public function process($key)
	{
		if (empty($key))
		{
			return;
		}

		R::begin();
		$context = new ProcessingContext();
		$context->key = $key;
		$this->beforeProcessing($context);

		$subProcessors = $this->getSubProcessors();
		$urlMap = [];
		$urks = [];
		foreach ($subProcessors as $processor)
		{
			foreach ($processor->getURLs($key) as $url)
			{
				if (!isset($urlMap[$url]))
				{
					$urlMap[$url] = [];
				}
				$urlMap[$url] []= $processor;
				$urls[$url] = $url;
			}
		}

		$documents = Downloader::downloadMulti($urls);

		try
		{
			foreach ($subProcessors as $subProcessor)
			{
				$subDocuments = [];
				foreach ($urlMap as $url => $urlProcessors)
				{
					if (in_array($subProcessor, $urlProcessors))
					{
						$subDocuments []= $documents[$url];
					}
				}
				$subProcessor->process($subDocuments, $context);
			}
			$this->afterProcessing($context);
			R::commit();
		}
		catch (Exception $e)
		{
			R::rollback();
			throw $e;
		}

		return $context;
	}
}
