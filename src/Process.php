<?php
/**
 * @author    jan huang <bboyjanhuang@gmail.com>
 * @copyright 2016
 *
 * @link      https://www.github.com/janhuang
 * @link      http://www.fast-d.cn/
 */

namespace FastD\Swoole;


use swoole_process;

/**
 * Process manager
 *
 * Class Process
 * @package FastD\Swoole
 */
class Process
{
    /**
     * @var Server
     */
    protected $server;

    /**
     * @var swoole_process
     */
    protected $process;

    /**
     * @var swoole_process[]
     */
    protected $processes = [];

    /**
     * @var callable
     */
    protected $callback;

    /**
     * @var bool
     */
    protected $stdout = false;

    /**
     * @var bool
     */
    protected $pipe = true;

    /**
     * @var string
     */
    protected $name;

    /**
     * @var bool
     */
    protected $daemonize = false;

    /**
     * Process constructor.
     * @param $name
     * @param $callback
     * @param bool $stdout
     * @param bool $pipe
     */
    public function __construct($name = null, $callback = null, $stdout = false, $pipe = true)
    {
        $this->name = $name;

        $this->stdout = $stdout;

        $this->pipe = $pipe;

        $this->callback = null === $callback ? [$this, 'handle'] : $callback;

        $this->process = new swoole_process($this->callback, $stdout, $pipe);
    }

    /**
     * @param $name
     * @return $this
     */
    public function name($name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * @return mixed
     */
    public function daemon()
    {
        $this->daemonize = true;

        return $this;
    }

    /**
     * @param int $size
     * @return mixed
     */
    public function read($size = 8192)
    {
        return $this->process->read($size);
    }

    /**
     * @param $data
     * @return mixed
     */
    public function write($data)
    {
        return $this->process->write($data);
    }

    /**
     * @param Server $server
     * @return $this
     */
    public function setServer(Server $server)
    {
        $this->server = $server;

        return $this;
    }

    /**
     * @param $signo
     * @param callable $callback
     * @return mixed
     */
    public function signal($signo, callable $callback)
    {
        return process_signal($signo, $callback);
    }

    /**
     * @return void
     */
    public function wait(callable $callback, $blocking = true)
    {
        while ($ret = process_wait($blocking)) {
            $callback($ret);
        }
    }

    /**
     * @param $pid
     * @param int $signo
     * @return int
     */
    public function kill($pid, $signo = SIGTERM)
    {
        return process_kill($pid, $signo);
    }

    /**
     * @param $pid
     * @return int
     */
    public function exists($pid)
    {
        return process_kill($pid, 0);
    }

    /**
     * @return mixed
     */
    public function start()
    {
        if (!empty($this->name)) {
            $this->process->name($this->name);
        }
        if (true === $this->daemonize) {
            $this->process->daemon();
        }

        return $this->process->start();
    }

    /**
     * @param int $length
     * @return int
     */
    public function fork($length = 1)
    {
        // run parent process
        $this->start();
        // new sub process
        for ($i = 0; $i < $length; $i++) {
            $process = new static($this->name, $this->callback, $this->stdout, $this->pipe);
            if (!empty($this->name)) {
                $process->name($this->name);
            }
            if (true === $this->daemonize) {
                $process->daemon();
            }
            $pid = $process->start();
            if (false === $pid) {
                return -1;
            }
            $this->processes[$pid] = $process;
        }
        return 0;
    }

    /**
     * @return swoole_process[]
     */
    public function getChildProcesses()
    {
        return $this->processes;
    }

    /**
     * @return swoole_process
     */
    public function getProcess()
    {
        return $this->process;
    }

    /**
     * Process handle
     *
     * @param $swoole_process
     * @return callable
     */
    public function handle(swoole_process $swoole_process){}
}