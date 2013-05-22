BTS Server: Installation Instructions
=====================================

As of May 21, 2013, no graphical installer currently exists in BTS. Installation is a difficult, manual process at this point. Since BTS is currently in the Alpha stage, we cannot guarantee that these manual installation instructions will function. However, they are provided to assist testers.

Server
------

_These instructions were written for version 0.1.0-alpha of the BTS server. They may break if using a release older than 0.1 or the latest repository version._

1. Download the latest release of BTS Server from SourceForge <s>or GitHub (TODO)</s>, or checkout the code with Git.
2. Configure the web server (e.g. Apache, nginx).
   1. If you downloaded an archive from SourceForge, extract the files to a directory so that the `public` folder is accessible over HTTP -- preferably, using `public` as the document root.
   2. If you used Git, ensure that the web server only serves from the `public` folder.
3. Create a MySQL database and user to be used with BTS.
4. Use a MySQL administration tool (e.g. phpMyAdmin or the MySQL Workbench), install the BTS schema in [`application/configs/sql/bts-schema.sql`](https://github.com/frederickding/barcodetickets-server/blob/master/application/configs/sql/bts-schema.sql).
5. Copy [`application/configs/database.ini.dist`](https://github.com/frederickding/barcodetickets-server/blob/master/application/configs/database.ini.dist) to `application/configs/database.ini`.
   1. Fill in your MySQL server hostname, username, password, schema, and save this `database.ini` file.
   2. If your system does _not_ support PDO MySQL (rare among modern PHP installations), enter `'Mysqli'` as the value for the `resources.db.adapter` option.
6. Copy [`application/configs/bts.ini.dist`](https://github.com/frederickding/barcodetickets-server/blob/master/application/configs/bts.ini.dist) to `application/configs/bts.ini`. _It is imperative that you leave the .dist file in place!_
   1. Visit http://barcodetickets.sourceforge.net/utils/generate-hash.php to generate a secure hash for your installation. This is used to seed event encryption keys and API keys.

BTS should now be functional, assuming Zend Framework and PEAR are available in the right locations.

At this point, the BTS system contains no users, API clients, events or tickets. To do anything on the server side, you should first create an administrative user.

### Creating an Administrative User (workaround)

For the moment, the only means of doing so is by creating a file and executing it to run a certain method.

```php
<?php
class DebugController extends Zend_Controller_Action
{
	public function indexAction ()
	{
		$U = new Bts_Model_Users();
		$userId = $U->insertUser('YOURUSERNAME', 'YOURPASSWORD', array(
			'email' => 'YOUREMAIL@YOURDOMAIN.COM',
			'status' => 1
		));
		echo $userId;
	}
}
```

1. Create a file called `DebugController.php` in `application/controllers` with these contents (replace with your own information):
2. Visit the root of your BTS installation, and append `/debug/` to the URL (e.g. if your installation is located at http://example.com/bts/, visit http://example.com/bts/debug/).
3. If the application shows the number 1 (i.e. the sequential user ID of the very first user), you have succeeded in creating your user.
4. _Delete the `DebugController.php` file for security._
5. Log in from the root of your BTS application.