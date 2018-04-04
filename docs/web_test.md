WebTest Class:  'web_test.php'
==============================

This class is the core of the PHP Web Tester product, which is an ultra lightweight testing framework for creating repeatable, instrumented builds of PHP-based software products.

For example usage of this class, see [PHP Web Tester](https://github.com/cubiclesoft/php-web-tester).

WebTest::__construct($testdir, $logfilename, $baseurl, $phantomjs = false)
--------------------------------------------------------------------------

Access:  public

Parameters:

* $testdir - A string containing the full path to the location where test paths will be stored on the system.  The specified path must also be accessible via $baseurl.
* $logfilename - A string containing the full path and filename of where logging output will be stored.
* $baseurl - A string containing the full base URL that equals $testdir.
* $phantomjs - A boolean of false (this feature is not implemented, Default is false).

Returns:  Nothing.

This function initializes a WebTest instance.

WebTest::__destruct()
---------------------

Access:  public

Parameters:  None.

Returns:  Nothing.

This function outputs the result of all tests performed as well as displaying information about the first time a specific test failed (if any).

WebTest::BuildPackage($cmd)
---------------------------

Access:  public

Parameters:

* $cmd - A string containing an executable command (e.g. a batch file/bash script).

Returns:  Nothing.

This function executes the command supplied and collects output.  If the supplied executable returns a value other than 0, then the function will declare it an error and stop.

WebTest::ExtractZIP($filename, $destname)
-----------------------------------------

Access:  public

Parameters:

* $filename - A string containing the full path and filename of the ZIP file to extract.
* $destname - A string containing the name of the test to extract the ZIP file into.

Returns:  A boolean of true on success, false otherwise.

This function extracts the specified ZIP file into the specified destination test name.  The return value can generally be ignored since a test likely won't exist at this point and, even if it does, using `NoError()` is the recommended approach.

WebTest::SwitchTo($name)
------------------------

Access:  public

Parameters:

* $name - A string containing the name of the test to switch to.

Returns:  A boolean of true on success, false otherwise.

This function switches the currently active test.

WebTest::NoError()
------------------

Access:  public

Parameters:  None.

Returns:  A boolean of true if the current test is not in an error state, false otherwise.

WebTest::CopyTo($destname)
--------------------------

Access:  public

Parameters:

* $name - A string containing the name of the test to copy the current test to.

Returns:  Nothing.

This function copies all of the files in the current test to the destination test and also clones the internal WebBrowser instance (e.g. cookies).

WebTest::CopyFrom($srcname)
---------------------------

Access:  public

Parameters:

* $name - A string containing the name of the test to replace the current test with.

Returns:  Nothing.

This function removes the current test files, copies all of the files in the destination test to current test, and also restores (clones) the internal WebBrowser instance (e.g. cookies).

WebTest::Remove()
-----------------

Access:  public

Parameters:  None.

Returns:  Nothing.

This function removes the current test and associated test files.

WebTest::GetTestDir()
---------------------

Access:  public

Parameters:  None.

Returns:  A string containing the base test dir plus the currently selected test (if any).

This function returns the current test directory.  This can be useful if a web form needs a system path on the local machine.  Do not use this function in conjunction with other functions in the WebTest class.

WebTest::Instrument($filename, $find, $replace, $instrumentlog = false)
-----------------------------------------------------------------------

Access:  public

Parameters:

* $filename - A string containing the relative filename from a package to modify.
* $find - A string containing the precise text to look for in the file.
* $replace - A string containing the precise text to replace the found text with.
* $instrumentlog - A boolean of false or a string containing the exact path and filename of a log file that will be created in the event of an error associated with this instrumentation (Default is false).

Returns:  Nothing.

This function finds the first match in the specified file for the current test and replaces it with the replacement text.

WebTest::GetBaseURL()
---------------------

Access:  public

Parameters:  None.

Returns:  A string containing the base URL used for the constructor plus the currently selected test (if any).

This function returns the current base test URL.  This can be useful if a web form needs a URL referencing a test path on the machine.

WebTest::ClearInstrumentLogs()
------------------------------

Access:  public

Parameters:  None.

Returns:  Nothing.

This function clears/removes the registered instrumenation logs.

WebTest::ProcessInstrumentLogs()
--------------------------------

Access:  public

Parameters:  None.

Returns:  A boolean of true if the instrumentation logs are empty, false otherwise.

This function processes the registered instrumentation logs for errors.  If any are found, an error message will be emitted (and the current test will fail).

WebTest::Run($url, $options = array(), $expected = 200, $usephantomjs = false)
------------------------------------------------------------------------------

Access:  public

Parameters:

* $url - A string containing a relative URL.
* $options - An array containing options to use for only this set of requests (Default is array()).
* $expected - An integer containing the expected response code.
* $usephantomjs - A boolean of false (this feature is not implemented, Default is false).

Returns:  A boolean of false for failed states, otherwise an array containing the results of the call.

This function wrap the WebBrowser::Process() function, calling it in the context of the current test.  This function processes a request for the resulting URL against the specified profile and the internal state of the WebBrowser instance.  When "auto" is used for $profile, it uses the "useragent" setting in the instance state.  $options is used to construct an options array for a RetrieveWebpage() call.

If the test has already failed or the request fails (e.g. HTTP 500 errors), then false is returned.

WebTest::DOMElementsExist($body, $selectors)
--------------------------------------------

Access:  public

Parameters:

* $body - A string containing HTML.
* $selectors - A string or an array of CSS3-style selectors.

Returns:  Nothing.

This function uses TagFilter to attempt to find at least one match for all of the input selectors.  A message will be emitted on success, an error otherwise (and the current test will fail).

WebTest::FormFieldsExist($form, $fields)
----------------------------------------

Access:  public

Parameters:

* $form - A single WebBrowserForm instance.
* $fields - An array of strings containing expected fields in the supplied form.

Returns:  Nothing.

This function attempts to find matching field names for all of the supplied fields.  A message will be emitted on success for each found field, an error otherwise (and the current test will fail).

WebTest::LoadHTML($body)
------------------------

Access:  public

Parameters:

* $body - A string containing HTML.

Returns:  An instance of TagFilterNode on success (at the root), a boolean of false otherwise.

This function uses TagFilter to parse the HTML into a DOM-ready interface.

WebTest::FileExists($filename)
------------------------------

Access:  public

Parameters:

* $filename - A string containing the relative filename in the current test.

Returns:  Nothing.

This function asserts the existence of a file.  If the file does not exist, then an error is emitted and the current test will fail.

WebTest::Test($val, $successmessage, $errormessage)
---------------------------------------------------

Access:  public

Parameters:

* $val - A boolean or other value (e.g. integer) that can evaluate to true or false.
* $successmessage - A string containing the text to display on success.
* $errormessage - A string containing the text to display on error.

Returns:  Nothing.

This function performs a generic true/false test.  On success, the message is displayed, otherwise the error is displayed (and the current test will fail).

WebTest::Message($message, $messagecode = "")
---------------------------------------------

Access:  public

Parameters:

* $message - A string containing the message to display.
* $messagecode - An optional string containing the message code to display (Default is "").

Returns:  A boolean of true.

This function displays the specified message and also writes the message to the log file.

WebTest::Warning($warningmessage, $warningcode)
-----------------------------------------------

Access:  public

Parameters:

* $warningmessage - A string containing the warning to display.
* $warningcode - An string containing the warning code to display.

Returns:  A boolean of false.

This function displays the specified warning with a stack trace, increments the number of warnings, and also writes the message with stack trace to the log file.

WebTest::Error($errormessage, $errorcode)
-----------------------------------------

Access:  public

Parameters:

* $errormessage - A string containing the error to display.
* $errorcode - An string containing the error code to display.

Returns:  A boolean of false.

This function displays the specified error with a stack trace, increments the number of errors, and also writes the message with stack trace to the log file.

If there is no current test, the script will exit right away.  Otherwise, if this is the first error for the current test, then the information stored in the log file will be stored for later use with the class destructor and the application will continue.

WebTest::EmptyDirectory($path, $exclude = array(), $deletesubdirs = false)
--------------------------------------------------------------------------

Access:  public

Parameters:

* $path - A string containing a full system path to empty.
* $exclude - An array of strings where the keys are full path + filenames to exclude from being deleted (Default is array()).
* $deletesubdirs - A boolean that indicates whether or not to traverse subdirectories (Default is false).

Returns:  Nothing.

This somewhat internal function deletes files (and optionally subdirectories) in the specified $path except for any excluded paths/files.

WebTest::CopySourceDir($srcdir, $destdir, $createdestdir, $recurse = true, $exclude = array())
----------------------------------------------------------------------------------------------

Access:  public

Parameters:

* $srcdir - A string containing a source directory to copy from.
* $destdir - A string containing a destination directory to copy to.
* $createdestdir - A boolean indicating whether or not to create the destination directory (recursively).
* $recurse - A boolean indicating whether or not to copy subdirectories in the source directory (Default is true).
* $exclude - An array of strings where the keys are full path + filenames to exclude from being copied (Default is array()).

Returns:  Nothing.

This somewhat internal function copies entire source trees from $srcdir to $destdir except for any excluded paths/files.
