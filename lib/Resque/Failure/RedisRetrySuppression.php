<?php

namespace Resque\Failure;
use \Resque_Failure_Redis;
use \Resque;
use stdClass;

class RedisRetrySuppression extends Resque_Failure_Redis {
	/**
	 * Initialize a failed job class and save it (where appropriate).
	 *
	 * @param object $payload Job payload that failed.
	 * @param object $exception Instance of the exception that was thrown by the failed job.
	 * @param object $worker Instance of Resque_Worker that received the job.
	 * @param string $queue The name of the queue the job was fetched from.
	 */
	public function __construct($payload, $exception, $worker, $queue) {
		if (!isset($payload['retrying']) || !$payload['retrying'] || $payload['retryDelay'] <= 0) {
			return parent::__construct($payload, $exception, $worker, $queue);
		}

		$retryDelay = $payload['retryDelay'];

		$data = new stdClass;
		$data->failed_at = strftime('%a %b %d %H:%M:%S %Z %Y');
		$data->payload = $payload;
		$data->exception = get_class($exception);
		$data->error = $exception->getMessage();
		$data->backtrace = explode("\n", $exception->getTraceAsString());
		$data->worker = (string)$worker;
		$data->queue = $queue;
		$data->retry_delay = $retryDelay;
		$data->retried_at = strftime('%a %b %d %H:%M:%S %Z %Y', $payload['retryingAt']);
		$data = json_encode($data);

		Resque::redis()->rpush('failed', $data);
	}

	// /**
	//  * Return the redis key used to log the failure information
	//  *
	//  * @param 	Resque_Job 	$job
	//  * @param 	string
	//  */
	// protected function redisRetryKey($job) {
	// 	return 'failed-retrying:'.$job->retryKey;
	// }

	// /**
	//  * Clean up the retry information from Redis
	//  *
	//  * @param 	Resque_Job 	$job
	//  */
	// protected function clearRetryKey($job) {
	// 	$retryKey = $this->redisRetryKey($job);

	// 	Resque::redis()->del($retryKey);
	// }
}