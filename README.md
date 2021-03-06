# BookMark'd

BookMark'd saves links.  Add the Bookmarklet to your toolbar and start storing links.

Search through your links with ease.

[Live Demo](http://markd.6km.me).  Please be kind?

## Current Features

* Convenient Bookmarklet
* Link search
* Deleting Bookmarks
* Periodic site content updated (incase you bookmark a homepage or some other web site that updates it's content)
  * By default it's set to 2 weeks.  This can be lowered or increased, but 2 weeks seems like a nice timeframe
  * You have to [uncomment the lines in cron.php](https://github.com/kmwallio/BookMarkd/wiki/Periodic-Updating) for it to work.  We encourage bookmarking static site content into BookMark'd so you can avoid having to run this
  * Remarking a website will update its contents in the database

## Planned Features

* Pagination
* Improved searching algorithm
* Improved searching options
	* exclude terms, by site or tags
* Tagging
	* Auto-tagging for new links
* Improved site stripping (oh la la)
	* Remove menus, footers, just get content
* Privacy lock, not open and viewable
* Related pages
	* Not tagged, but based on content
* Templates so you can "rebrand" the pages
	* **Idea:** Mark your Octopress/Jekyll blog posts and have a custom site search?

#### Need More Info (but hope to do someday)

* Character handling (I need to learn about encodings...?) (More likely than what follows)
* Reading/Getting JavaScript generated content

# Requirements

* PHP
	* cURL
	* PDO
* SQLite
* Composer

# Installation

1. Download [archive](https://github.com/kmwallio/BookMarkd/archive/master.zip).
2. Unzip and upload to server
3. Run `composer install`
4. Run setup.php
5. Setup a cron job to run `cron.php`.  Please note that the php path may be incorrect…
	* As long as it's more than ~15 minutes apart, it should be fine.  This job is what actually adds the "marked" links into the database.  Links cannot be searched for until they are added to the database, they can be viewed in the admin panel though. *I need to update the cron script to be slightly smarter for people who post a large number of links.  We were running into an issue where we would lose the content of some pages.*

### Credits

* [Bootstrap](http://twitter.github.io/bootstrap) - Provides our nice GUI
* [Chuggnutt](http://www.chuggnutt.com/stemmer) - Quick work stemming
* [ircmaxell/password_compat](https://github.com/ircmaxell/password_compat) - Secure password hashing?

#### Small print
BookMark'd is a project I created to learn how to use [Bootstrap](http://twitter.github.io/bootstrap) and the [PDO Library](http://php.net/manual/en/book.pdo.php).  It probably sucks, but I still use it.
