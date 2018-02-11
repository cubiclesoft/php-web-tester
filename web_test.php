<?php
	// Web Test class.
	// (C) 2018 CubicleSoft.  All Rights Reserved.

	class WebTest
	{
		protected $testinfo, $currtest, $testdir, $logfilename, $fp, $baseurl, $phantomjs, $web, $htmloptions, $numwarnings, $numerrors;

		public function __construct($testdir, $logfilename, $baseurl, $phantomjs = false)
		{
			if (!class_exists("WebBrowser", false))  require_once str_replace("\\", "/", dirname(__FILE__)) . "/support/web_browser.php";
			if (!class_exists("TagFilter", false))  require_once str_replace("\\", "/", dirname(__FILE__)) . "/support/tag_filter.php";

			$this->testinfo = array();
			$this->currtest = false;
			$this->testdir = $testdir;
			$this->logfilename = $logfilename;
			$this->baseurl = $baseurl;
			$this->phantomjs = $phantomjs;

			@mkdir($this->testdir, 0777, true);

			$this->fp = fopen($logfilename, "wb");
			$this->web = new WebBrowser(array("extractforms" => true));
			$this->htmloptions = TagFilter::GetHTMLOptions();
			$this->numwarnings = 0;
			$this->numerrors = 0;
		}

		public function __destruct()
		{
			$this->currtest = false;

			if (!$this->numerrors && !$this->numwarnings)  $this->Message("All tests passed!", "passed");
			else if (!$this->numerrors)  $this->Message("All tests passed, but there were " . $this->numwarnings . " warnings.", "passed_with_warnings");
			else
			{
				foreach ($this->testinfo as $key => $info)
				{
					$this->Message($key . ":  " . ($info["error"] !== false ? "ERROR\n" . $info["error"] : "OK"), "test_status");
				}

				$this->Message("There were " . $this->numerrors . " errors and " . $this->numwarnings . " warnings.  Check '" . $this->logfilename . "' for details.", "failed");
			}
		}

		public function BuildPackage($cmd)
		{
			$this->Message("Building package - started:  '" . $cmd  . "'", "buildpackage_start");
			exec($cmd . " 2>&1", $output, $retval);

			if ($retval !== 0)  $this->Error("BuildPackage failed.  Return value was " . $retval . ".  Output:\n" . implode("\n", $output), "buildpackage_failed");

			$this->Message("Building package - finished", "buildpackage_finish");
		}

		public function ExtractZIP($filename, $destname)
		{
			if (!function_exists("zip_open"))  return $this->Error("ExtractZIP failed.  Required function 'zip_open' does not exist.  Please enable ZIP file functionality for PHP in your configuration.", "extractzip_missingfunction");

			if (!file_exists($filename))  return $this->Error("ExtractZIP failed.  '" . $filename . "' does not exist.", "extractzip_missingzipfile");

			$zip = @zip_open($filename);
			if (!is_resource($zip))  return $this->Error("ExtractZIP failed.  Unable to open ZIP file '" . $filename . "'.");

			$this->Message("Extracting ZIP file - preparing directory.", "extractzip_init");

			@mkdir($this->testdir . "/" . $destname, 0777, true);
			$this->EmptyDirectory($this->testdir . "/" . $destname . "/", array(), true);

			$this->testinfo[$destname] = array(
				"error" => false,
				"instrumentlogs" => array(),
				"web" => clone $this->web
			);

			$this->Message("Extracting ZIP file - started.", "extractzip_start");

			$entry = @zip_read($zip);
			while (is_resource($entry))
			{
				$name = @zip_entry_name($entry);
				$name = str_replace("\\", "/", $name);

				if (substr($name, -1) === "/")
				{
					@mkdir($this->testdir . "/" . $destname . "/" . $name, 0777, true);
				}
				else if (@zip_entry_open($zip, $entry))
				{
					$filedata = @zip_entry_read($entry, @zip_entry_filesize($entry));
					@zip_entry_close($entry);

					if ($filedata === false)  return $this->Error("An error occurred while reading '" . $name . "'.", "extractzip_filereaderror");
					else
					{
						$dirpath = dirname($name);
						if ($dirpath != "" && $dirpath != ".")
						{
							@mkdir($this->testdir . "/" . $destname . "/" . $dirpath, 0777, true);
						}

						if (file_put_contents($this->testdir . "/" . $destname . "/" . $name, $filedata) === false)  return $this->Error("An error occurred while writing '" . $name . "'.", "extractzip_filewriteerror");
					}
				}

				$entry = @zip_read($zip);
			}

			@zip_close($zip);

			$this->Message("Extracting ZIP file - finished.", "extractzip_finish");

			return true;
		}

		public function SwitchTo($name)
		{
			if (!isset($this->testinfo[$name]))  return $this->Error("Unknown name '" . $name . "'.", "switchto_unknown_name");

			if ($name !== $this->currtest)
			{
				$this->currtest = $name;

				if ($this->testinfo[$name]["error"] !== false)  $this->Message("Skipping due to a previous error in '" . $name . "'.", "switchto_skipping_test");
				else  $this->Message("Switched to '" . $name . "'.", "switchto_success");
			}

			return true;
		}

		public function NoError()
		{
			return ($this->currtest !== false && $this->testinfo[$this->currtest]["error"] === false);
		}

		public function CopyTo($destname)
		{
			if ($this->currtest === false || $this->testinfo[$this->currtest]["error"] !== false || $this->currtest === $destname)  return;

			$this->Message("Copying '" . $this->currtest . "' to '" . $destname . "' - preparing directory.", "copyto_init");

			@mkdir($this->testdir . "/" . $destname, 0777, true);
			$this->EmptyDirectory($this->testdir . "/" . $destname . "/", array(), true);

			$this->Message("Copying '" . $this->currtest . "' to '" . $destname . "' - started.", "copyto_start");

			$this->CopySourceDir($this->testdir . "/" . $this->currtest, $this->testdir . "/" . $destname, true);

			$this->testinfo[$destname] = $this->testinfo[$this->currtest];
			$this->testinfo[$destname]["web"] = clone $this->testinfo[$this->currtest]["web"];

			$this->Message("Copying '" . $this->currtest . "' to '" . $destname . "' - finished.", "copyto_finish");
		}

		public function CopyFrom($srcname)
		{
			if ($this->currtest === false || $this->testinfo[$this->currtest]["error"] !== false || $srcname === $this->currtest)  return;

			$this->Message("Copying '" . $srcname . "' to '" . $this->currtest . "' - preparing directory.", "copyfrom_init");

			@mkdir($this->testdir . "/" . $this->currtest, 0777, true);
			$this->EmptyDirectory($this->testdir . "/" . $this->currtest . "/", array(), true);

			$this->Message("Copying '" . $srcname . "' to '" . $this->currtest . "' - started.", "copyfrom_start");

			$this->CopySourceDir($this->testdir . "/" . $srcname, $this->testdir . "/" . $this->currtest, true);

			$this->testinfo[$this->currtest] = $this->testinfo[$srcname];
			$this->testinfo[$this->currtest]["web"] = clone $this->testinfo[$srcname]["web"];

			$this->Message("Copying '" . $srcname . "' to '" . $this->currtest . "' - finished.", "copyfrom_finish");
		}

		public function Remove()
		{
			if ($this->currtest === false)  return;

			$this->Message("Removing '" . $this->currtest . "' - start.", "remove_finish");

			$this->EmptyDirectory($this->testdir . "/" . $this->currtest . "/", array(), true);
			@rmdir($this->testdir . "/" . $this->currtest);

			unset($this->testinfo[$this->currtest]);

			$this->Message("Removing '" . $this->currtest . "' - finished.", "remove_finish");

			$this->currtest = false;
		}

		public function GetTestDir()
		{
			return $this->testdir . ($this->currtest !== false ? "/" . $this->currtest : "");
		}

		public function Instrument($filename, $find, $replace, $instrumentlog = false)
		{
			if ($this->currtest === false || $this->testinfo[$this->currtest]["error"] !== false)  return false;

			$this->FileExists($filename);

			$filename = $this->testdir . "/" . $this->currtest . "/" . $filename;
			$data = file_get_contents($filename);

			$pos = strpos($data, $find);
			if ($pos === false)  return $this->Error("Unable to find '" . $find . "' in the file '" . $filename . "'.", "instrument_find_error");
			$data = substr($data, 0, $pos) . $replace . substr($data, $pos + strlen($find));

			file_put_contents($filename, $data);

			if ($instrumentlog !== false)  $this->testinfo[$this->currtest]["instrumentlogs"][] = $instrumentlog;

			$this->Message("Instrumented '" . $filename . "'.  Replaced first instance of '" . $find . "' with '" . $replace . "'.\n", "instrument_success");

			return true;
		}

		public function GetBaseURL()
		{
			return $this->baseurl . ($this->currtest !== false ? "/" . $this->currtest : "");
		}

		public function ClearInstrumentLogs()
		{
			if ($this->currtest === false || $this->testinfo[$this->currtest]["error"] !== false)  return;

			foreach ($this->testinfo[$this->currtest]["instrumentlogs"] as $logfile)  @unlink($logfile);
		}

		public function ProcessInstrumentLogs()
		{
			if ($this->currtest === false)  return false;

			foreach ($this->testinfo[$this->currtest]["instrumentlogs"] as $logfile)
			{
				if (file_exists($logfile))
				{
					$data = trim(file_get_contents($logfile));
					@unlink($logfile);

					if ($data !== "")  return $this->Error("The instrumented log '" . $logfile . "' contains error information:\n\n" . $data . "\n", "log_instrument_error");
				}
			}

			return true;
		}

		public function Run($url, $profile = "auto", $options = array(), $usephantomjs = false)
		{
			if ($this->currtest === false || $this->testinfo[$this->currtest]["error"] !== false)  return false;

			$this->ClearInstrumentLogs();

			$url = HTTP::ConvertRelativeToAbsoluteURL($this->baseurl . "/" . $this->currtest . "/", $url);

			if ($usephantomjs)
			{
				$this->Message("Running '" . $url . "' - started (PhantomJS).", "run_start");

				// Haven't run into a valid use-case to go to the effort of implementing full PhantomJS support.
				// Various bits of instrumenting and code exist due to possibly needing this at one point but ended up not needing it.
				return $this->Error("Not implemented...yet.", "not_implemented");
			}
			else
			{
				$this->Message("Running '" . $url . "' - started (WebBrowser)." . (isset($options["postvars"]) ? "\n" . var_export($options["postvars"], true) : ""), "run_start");

				$result = $this->testinfo[$this->currtest]["web"]->Process($url, $profile, $options);
				if (!$result["success"])
				{
					$this->ProcessInstrumentLogs();

					return $this->Error("A WebBrowser class or HTTP error occurred:  " . $result["error"] . " (" . $result["errorcode"] . ")", "run_webbrowser_error");
				}
				else if ((int)$result["response"]["code"] !== 200)
				{
					$this->ProcessInstrumentLogs();

					return $this->Error("The server responded with " . $result["response"]["line"], "run_server_error");
				}

				if (!$this->ProcessInstrumentLogs())  return false;

				$this->Message("Running '" . $url . "' - finished.", "run_finish");

				return $result;
			}
		}

		public function DOMElementsExist($body, $selectors)
		{
			if (is_string($selectors))  $selectors = array($selectors);

			$html = TagFilter::Explode("<div>" . $body . "</div>", $this->htmloptions);
			foreach ($selectors as $selector)
			{
				$result = $html->Find($selector);
				if (!$result["success"])  $this->Error("An error occurred while parsing/matching '" . $selector . "':  " . $result["error"] . " (" . $result["errorcode"] . ").\n\n" . $body, "domelements_match_error");
				else if (!count($result["ids"]))  $this->Error("Unable to match the selection '" . $selector . "'.\n\n" . $body, "domelements_match_not_found");
				else  $this->Message("Selection '" . $selector . "' matched " . (count($result["ids"]) === 1 ? "1 element" : count($result["ids"]) . " elements") . ".", "domelements_match_found");
			}
		}

		public function FormFieldsExist($form, $fields)
		{
			if ($this->currtest === false || $this->testinfo[$this->currtest]["error"] !== false)  return;

			foreach ($fields as $name)
			{
				if (!count($form->FindFormFields($name)))  $this->Error("Unable to find form field '" . $name . "'.", "formfields_field_not_found");
				else  $this->Message("Found form field '" . $name . "'.", "formfields_field_found");
			}
		}

		public function LoadHTML($body)
		{
			$html = TagFilter::Explode($body, $this->htmloptions);

			return $html->Get();
		}

		public function FileExists($filename)
		{
			if ($this->currtest === false || $this->testinfo[$this->currtest]["error"] !== false)  return;

			if (!file_exists($this->testdir . "/" . $this->currtest . "/" . $filename))  $this->Error("'" . $this->testdir . "/" . $this->currtest . "/" . $filename . "' does not exist.", "file_not_found");
		}

		public function Test($val, $successmessage, $errormessage)
		{
			if ($this->currtest === false || $this->testinfo[$this->currtest]["error"] !== false)  return;

			if ($val)  $this->Message($successmessage, "test_success");
			else  $this->Error($errormessage, "test_failed");
		}

		public function Message($message, $messagecode = "")
		{
			$info = "[INFO] [" . date("Y-m-d H:i:s") . "]" . ($this->currtest !== false ? " [" . $this->currtest . "]" : "") . ($messagecode != "" ? " [" . $messagecode . "]" : "") . "\n\t" . str_replace("\n", "\n\t", $message) . "\n\n";

			echo $info;
			fwrite($this->fp, $info);

			return true;
		}

		public function Warning($warningmessage, $warningcode)
		{
			$info = "[WARNING] [" . date("Y-m-d H:i:s") . "]" . ($this->currtest !== false ? " [" . $this->currtest . "]" : "") . " [" . $warningcode . "]\n\t" . str_replace("\n", "\n\t", $warningmessage) . "\n\n";

			ob_start();
			debug_print_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS);
			$info .= ob_get_contents() . "\n";
			ob_end_clean();

			echo $info;
			fwrite($this->fp, $info);

			$this->numwarnings++;

			return false;
		}

		public function Error($errormessage, $errorcode)
		{
			$info = "[ERROR] [" . date("Y-m-d H:i:s") . "]" . ($this->currtest !== false ? " [" . $this->currtest . "]" : "") . " [" . $errorcode . "]\n\t" . str_replace("\n", "\n\t", $errormessage) . "\n\n";

			ob_start();
			debug_print_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS);
			$info .= ob_get_contents() . "\n";
			ob_end_clean();

			echo $info;
			fwrite($this->fp, $info);

			$this->numerrors++;

			if ($this->currtest === false)  exit();
			else if ($this->testinfo[$this->currtest]["error"] === false)  $this->testinfo[$this->currtest]["error"] = $info;

			return false;
		}

		public function EmptyDirectory($path, $exclude = array(), $deletesubdirs = false)
		{
			$dir = opendir($path);
			if ($dir !== false)
			{
				while (($file = readdir($dir)) !== false)
				{
					if ($file != "." && $file != ".." && !isset($exclude[$path . $file]))
					{
						if (!$deletesubdirs || is_file($path . $file) || is_link($path . $file))  @unlink($path . $file);
						else if ($deletesubdirs && is_dir($path . $file))
						{
							$this->EmptyDirectory($path . $file . "/", $exclude, true);
							@rmdir($path . $file);
						}
					}
				}

				closedir($dir);
			}
		}

		public function CopySourceDir($srcdir, $destdir, $createdestdir, $recurse = true, $exclude = array())
		{
			if ($createdestdir)  @mkdir($destdir, 0777, true);

			$dir = @opendir($srcdir);
			if ($dir)
			{
				while (($file = readdir($dir)) !== false)
				{
					if ($file != "." && $file != ".." && !isset($exclude[$srcdir . "/" . $file]))
					{
						if (is_dir($srcdir . "/" . $file))
						{
							if ($recurse)  $this->CopySourceDir($srcdir . "/" . $file, $destdir . "/" . $file, true, true);
						}
						else
						{
							file_put_contents($destdir . "/" . $file, file_get_contents($srcdir . "/" . $file));
						}
					}
				}

				@closedir($dir);
			}
		}
	}
?>