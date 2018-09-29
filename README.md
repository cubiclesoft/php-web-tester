WebTest PHP Testing Framework
=============================

An ultra lightweight testing framework for creating repeatable, instrumented builds of PHP-based software products.

Most PHP frameworks that I've run into are typically big, bloated affairs designed for unit testing libraries rather than whole applications.  I'm sure they are useful but they don't fit my particular needs.  What I needed was a tool for building an installable package out of a directory tree (e.g. a proposed release ZIP file of one of the larger CubicleSoft PHP software products) and then using the package to create isolated live (i.e. no mocks), fully instrumented, fully scripted environments, and running many, many web requests against a localhost web server.  Behind the scenes, the Ultimate Web Scraper Toolkit does the heavy lifting of testing an entire web application prior to software release.  This tool is designed for those who create large open source PHP-based software products intended for deployment by other people and where maintaining sanity is important.  It didn't really exist, so I made it.  You're welcome.

[![Donate](https://cubiclesoft.com/res/donate-shield.png)](https://cubiclesoft.com/donate/)

Features
--------

* A single PHP class called WebTest which you use to write some basic PHP code.
* Just one PHP script is all you need to write to perform complete product verification.  Get complete code coverage for most applications in as little as a few hundred lines of code.
* Multiple environment cloning support (clone, test one path, and later completely revert to a previous state).
* Instrumented builds.  Modify `.php` and other files in specific ways to avoid adding hacks to the original software to enable testing.
* DOM verification.  Verify that specific DOM elements exist at various points in time.
* HTML form verification.  Verify that specific form fields exist inside an extracted HTML form.
* All the benefits of Ultimate Web Scraper Toolkit (e.g. automated form extraction, cookie handling, TagFilter).
* Has a liberal open source license.  MIT or LGPL, your choice.
* Designed for relatively painless integration into your project.
* Sits on GitHub for all of that pull request and issue tracker goodness to easily submit changes and ideas respectively.

Getting Started
---------------

Download or clone this repository and put it somewhere.  Next, create your testing PHP file (e.g. `test_suite.php`) for your application.

```php
<?php
	// YOUR APP test suite.
	// (C) [Year] [Owner].  All Rights Reserved.

	if (!isset($_SERVER["argc"]) || !$_SERVER["argc"])
	{
		echo "This file is intended to be run from the command-line.";

		exit();
	}

	// Root path.
	$rootpath = str_replace("\\", "/", dirname(__FILE__));
	chdir($rootpath);

	require_once $rootpath . "/web-tester/web_test.php";

	// Initialize.
	$test = new WebTest($rootpath . "/tests", $rootpath . "/results.log", "http://localhost/path/to/tests");
	$test->BuildPackage("build.bat");  // Yup, I run Windows!
	$test->ExtractZIP($rootpath . "/yourapp.zip", "initial");
```

A lot is going on here.  The first step is to normalize the environment (e.g. making sure the PHP script is running in the correct directory).  The `WebTest` class is initialized with important information about the system it is running on - the directory for tests, the result log file location, and the base web URL of the tests.  `BuildPackage()` then runs the build process for the software product.  When that is done, `ExtractZIP()` extracts the ZIP file created by `BuildPackage()` to the `tests/initial` directory.  At this point, you might think you are ready to start writing tests, but the next step is what differentiates this product from everything else out there:  Instrumentation.

Instrumentating Builds
----------------------

Instrumentation, in this context, is the process of slightly modifying an application so that it emits various error messages in a detectable way that might not have been caught in development.  Instrumentation also makes it possible to access information that might be of a sensitive nature in a production environment (e.g. duplicating XSRF tokens in a way that make them more easily extracted from the HTML).  Continuing with the example:

```php
	// Instrument the installer.
	$test->Message("Instrumenting installer.");
	$test->SwitchTo("initial");
	$test->Instrument("install.php", "<" . "?php", "<" . "?php require \"" . $rootpath . "/web-tester/instruments/php_instrument.php\";", $rootpath . "/web-tester/instruments/instrumented_php.log");
	$test->Instrument("install.php", "<link rel=\"stylesheet\" href=\"support/install.css\" type=\"text/css\" media=\"all\" />", "<script type=\"text/javascript\" src=\"/path/to/web-tester/instruments/js_instrument.js\"></script><link rel=\"stylesheet\" href=\"support/install.css\" type=\"text/css\" media=\"all\" />", $rootpath . "/web-tester/instruments/instrumented_js.log");
	$test->CopyTo("main");
```

Here the example switches to the previously extracted "initial" test (NOTE:  Extracting doesn't switch tests).  Then it modifies (instruments) two parts of the file `tests/initial/install.php` by finding and replacing the first match the function comes across.  If the file doesn't exist, instrumentation fails, testing fails, and an error is emitted.  The `instruments` directory of this repository contain two primary instrumenting files:  One for PHP and one for Javascript.  The Javascript file is intended for use with a real web browser (e.g. to see the application for yourself) and will write Javascript console errors back to PHP so that the test be logged.

Once instrumentation is complete, the entire `tests/initial` directory structure is cloned to `tests/main`.  This will allow the entire application to be reverted multiple times later on and see the application in various critical states with a real web browser if there are problems.

Instrumenting a build is what allows the class and any test suite built on top of it to remain tiny.  With this class, I've built feature-complete test suites in ~1,000 lines of code that fully exercise over 95% of a web application exceeding 30,000 lines of code.  Instrumentation is what makes tiny test suites possible.

Handling Web Requests
---------------------

Let's run through the basics of making web requests and handling the returned results.  Continuing with the previous example:

```php
	// Run the installer with default settings.
	$test->Message("Running installer with default settings.");
	$test->SwitchTo("main");
	if ($test->NoError())  $result = $test->Run("install.php");
	if ($test->NoError())  $test->DOMElementsExist($result["body"], "#contentwrap #content");
	if ($test->NoError())  $form = $result["forms"][0];
var_dump($form);
```

Here the example switches to the previously created "main" test (NOTE:  It's hopefully obvious by now that tests aren't automatically switched to when they are created - gotta do that yourself).  Then `Run()` is called, which calculates the final URL to retrieve, retrieves the URL, and then determines if any errors occurred.  The next step verifies that certain DOM elements exist in the original body and then the extracted form is retrieved and dumped out to the screen.

Note that `$test->NoError()` keeps the test script from running amok.  When an error occurs for a specific test, `NoError()` always returns true for that test (unless the test is overwritten).

Let's look at a few more options by continuing with the example:

```php
	if ($test->NoError())
	{
		$html = $test->LoadHTML($result["body"]);
		$rows = $html->Find('span.error');
		$test->Test((count($rows) === 1), "Exactly one checklist error as expected (the SSL alert).", count($rows) . " checklist errors.  Too many checklist errors.");
	}
	if ($test->NoError())  $test->FormFieldsExist($form, array("username", "password"));
	if ($test->NoError())
	{
		$form->SetFormValue("username", "admin");
		$form->SetFormValue("password", "passwordgoeshere");

		$result = $form->GenerateFormRequest();
		$result = $test->Run($result["url"], "auto", $result["options"]);
		$test->Test(strpos($result["body"], "The installation completed successfully.") !== false, "Installation successful.", "Installation failed.");
	}

	$test->Message("Instrumenting config.");
	$test->Instrument("config.php", "<" . "?php", "<" . "?php require \"" . $rootpath . "/web-tester/instruments/php_instrument.php\";", $rootpath . "/web-tester/instruments/instrumented_php.log");

	$test->CopyTo("installed");
```

First the code looks to make sure an installation checklist exists and only has one "error".  Then it verifies that the previously extracted form has "username" and "password" fields.  If all goes well, then those fields are set with values, the form is converted to a web request compatible format (i.e. the equivalent of hitting "Submit"), and then the next request is run and verified.

In this example, it is assumed that a file called `tests/main/config.php` was created by the installer.  Perhaps the application always includes this file very early on, so the test suite only needs to instrument the configuration.

Once all the post-install work is complete, the test suite copies the entire `tests/main` directory to `tests/installed`.  Maybe later on the test suite will need to restore the "main" test back to this critical state with a call to `$test->CopyFrom("installed")`.

That pretty much covers all of the major aspects of this tool.

Additional Notes
----------------

The `WebTest` class is designed to simplify a wide variety of tasks related to automating the testing of complex web applications in a product release environment.  My general recommendation is to be sure to bail out early and/or fall through with `NoError()` when tests start failing.  The destructor of WebTest outputs status information about how many warnings and errors occurred so you don't have to remember to do that.

This class isn't really designed for unit testing libraries.  There are plenty of tools out there that do those things already.

I generally tell an installer to use SQLite as the backend database for my test suite code.  SQLite databases are self-contained and therefore can be freely copied around.  Since I use [CSDB](https://github.com/cubiclesoft/csdb), SQL queries that work for one database product usually work unmodified for all the other database products that CSDB supports.

The goal of a test suite written with a tool like this is to execute the maximum number of lines of code with the fewest number of lines of code.  Toward that end, I walk through an application looking for major sections of code that haven't been added to the test suite, figure out how to test a few hundred lines with just two lines of code, add them, and usually toss in an `exit();` after I insert the test(s) to make sure they are passing.  Once all looks good, the `exit();` is removed and the test suite is run again to make sure that all tests still pass.

Be sure to read the documentation for the [WebTest class](https://github.com/cubiclesoft/php-web-tester/blob/master/docs/web_test.md).  The class itself is also only about 400 lines of code long, so it's not anything particularly special even if it is extremely useful.
