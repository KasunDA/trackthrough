--
-- Table structure for table `config` //verified for 1.4.2
--

CREATE TABLE `config` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `key_name` varchar(128) DEFAULT '',
  `value` varchar(1024) DEFAULT '',
  `type` tinyint(3) NOT NULL DEFAULT '13',

  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 AUTO_INCREMENT=1 ;




-- --------------------------------------------------------

--
-- Table structure for table `user` //verified for 1.4.2
--

CREATE TABLE `user` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `signin_id` varchar(16) NOT NULL,
  `password` blob,
  `iv` blob,
  `email` varchar(100) DEFAULT '',
  `first_name` varchar(16) DEFAULT '',
  `last_name` varchar(16) DEFAULT '',
   `icon_name` varchar(256) DEFAULT '',
  `created_at` datetime DEFAULT '0000-00-00 00:00:00',
  `signedin_at` datetime DEFAULT '0000-00-00 00:00:00',

  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 AUTO_INCREMENT=1 ;




--
-- Table structure for table `user_permission` //verified for 1.4.2
--

CREATE TABLE `user_permission` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(10) unsigned NOT NULL,
  `permission` tinyint(3) unsigned DEFAULT '0',
  `record_type` tinyint(3) unsigned DEFAULT '0',
  `record_id` int(10) unsigned DEFAULT '0',

  PRIMARY KEY  (`id`),
  FOREIGN KEY (`user_id`) REFERENCES `user` (`id`) ON DELETE CASCADE ON UPDATE NO ACTION
) ENGINE=InnoDB DEFAULT CHARSET=latin1 AUTO_INCREMENT=1 ;








--
-- Table structure for table `project` //verified for 1.4.2
--

CREATE TABLE `project` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `lead_id` int(10) unsigned NOT NULL,
  `team_id` int(10) unsigned DEFAULT NULL,
  `type` tinyint(3) unsigned NOT NULL,
  `enable_issue_tracking` tinyint(3) NOT NULL DEFAULT '0',
  `name` varchar(200) DEFAULT '',
  `description` text,
  `icon_name` varchar(256) DEFAULT NULL,
  `attachment_name` varchar(256) DEFAULT '',
  `created_at` datetime DEFAULT '0000-00-00 00:00:00',
  `updated_at` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00' ON UPDATE CURRENT_TIMESTAMP,
  `progress` tinyint(3) unsigned DEFAULT '0',

  PRIMARY KEY (`id`),
  FOREIGN KEY (`lead_id`) REFERENCES `user` (`id`) ON DELETE CASCADE ON UPDATE NO ACTION,
  KEY `team_id` (`team_id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 AUTO_INCREMENT=1 ;


-- --------------------------------------------------------

--
-- Table structure for table `task` //verified for 1.4.2
--

CREATE TABLE `task` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `parent_project_id` int(10) unsigned NOT NULL,
  `lead_id` int(10) unsigned DEFAULT NULL,
  `team_id` int(10) unsigned DEFAULT NULL,
  `type` tinyint(3) unsigned NOT NULL,
  `name` varchar(200) DEFAULT '',
  `description` text,
  `attachment_name` varchar(256) DEFAULT '',
  `status` tinyint(3) unsigned NOT NULL DEFAULT '3',
 `priority` tinyint(3) unsigned NOT NULL DEFAULT '113',
  `created_at` datetime DEFAULT '0000-00-00 00:00:00',
  `updated_at` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00' ON UPDATE CURRENT_TIMESTAMP,
  `assigned_at` datetime  DEFAULT '0000-00-00 00:00:00',
  `progress` tinyint(3) DEFAULT '0',

  PRIMARY KEY (`id`),
  FOREIGN KEY (`lead_id`) REFERENCES `user` (`id`) ON DELETE CASCADE ON UPDATE NO ACTION
) ENGINE=InnoDB DEFAULT CHARSET=latin1 AUTO_INCREMENT=1 ;


-- --------------------------------------------------------

--
-- Table structure for table `message` //verified for 1.4.2
--

CREATE TABLE `message` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `type_id` int(10) unsigned DEFAULT '0',
  `from_id` int(10) unsigned DEFAULT '0',
  `attachment_name` varchar(256) DEFAULT '',
  `cont` text,
  `subject` text,
  `status` tinyint(3) unsigned DEFAULT '0',
  `type` tinyint(3) unsigned DEFAULT '0',
  `date` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',

  PRIMARY KEY  (`id`),
  KEY `from_id` (`from_id`),
  KEY `type_id` (`type_id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 AUTO_INCREMENT=1 ;



-- --------------------------------------------------------

--
-- Table structure for table `message_board` //verified for 1.4.2
--

CREATE TABLE `message_board` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `message_id` int(10) unsigned NOT NULL DEFAULT '0',
  `user_id` int(10) unsigned DEFAULT NULL,
  `status` tinyint(3) unsigned NOT NULL DEFAULT '101',

  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  FOREIGN KEY (`message_id`) REFERENCES `message` (`id`) ON DELETE CASCADE ON UPDATE NO ACTION
) ENGINE=InnoDB DEFAULT CHARSET=latin1 AUTO_INCREMENT=1 ;


-- --------------------------------------------------------

--
-- Table structure for table `preference` //verified for 1.4.2
--

CREATE TABLE `preference` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(10) unsigned NOT NULL,
  `settings` text,

  PRIMARY KEY (`id`),
  FOREIGN KEY (`user_id`) REFERENCES `user` (`id`) ON DELETE CASCADE ON UPDATE NO ACTION
) ENGINE=InnoDB DEFAULT CHARSET=latin1 AUTO_INCREMENT=1 ;


-- --------------------------------------------------------

--
-- Table structure for table `bookmarks` //verified for 1.4.2
--

CREATE TABLE `bookmark` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(10) unsigned NOT NULL,
  `category` tinyint(3) NOT NULL,
  `category_id` int(10) unsigned NOT NULL,

  PRIMARY KEY (`id`),
  FOREIGN KEY (`user_id`) REFERENCES `user` (`id`) ON DELETE CASCADE ON UPDATE NO ACTION
 ) ENGINE=InnoDB DEFAULT CHARSET=latin1 AUTO_INCREMENT=1 ;
 
-- -------------------------------------------------------

--
-- Table structure for table `issue`  //verified for 1.4.2
--
CREATE TABLE `issue` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `project_id` int(10) unsigned NOT NULL,
  `user_id` int(10) unsigned NOT NULL,
  `type` tinyint(3) unsigned NOT NULL,
  `title` varchar(200) DEFAULT '',
  `description` text,
  `attachment_name` varchar(256) DEFAULT '',
  `status` tinyint(3) unsigned NOT NULL DEFAULT '103',
  `priority` tinyint(1) unsigned NOT NULL DEFAULT '113',

  `created_at` datetime DEFAULT '0000-00-00 00:00:00',
  `updated_at` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00' ON UPDATE CURRENT_TIMESTAMP,

  PRIMARY KEY (`id`),
  FOREIGN KEY (`user_id`) REFERENCES `user` (`id`) ON DELETE CASCADE ON UPDATE NO ACTION
) ENGINE=InnoDB DEFAULT CHARSET=latin1 AUTO_INCREMENT=1;

-- -------------------------------------------------------

--
-- Table structure for table `issue_task` //verified for 1.4.2
--

CREATE TABLE `issue_task` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `issue_id` int(10) unsigned NOT NULL,
  `task_id` int(10) unsigned NOT NULL,

   PRIMARY KEY  (`id`),
   FOREIGN KEY (`issue_id`) REFERENCES `issue` (`id`) ON DELETE CASCADE ON UPDATE NO ACTION,
   FOREIGN KEY (`task_id`) REFERENCES `task` (`id`) ON DELETE CASCADE ON UPDATE NO ACTION
) ENGINE=InnoDB DEFAULT CHARSET=latin1 AUTO_INCREMENT=1 ;


INSERT INTO `config` (`id`, `key_name`, `value`, `type`) VALUES
(1, 'company_name', 'Your Company Name', 13),
(2, 'from_email_address', 'admin_user@your_trackthrough.com', 13),

(3, 'website_name', 'TrackThrough', 13),
(4, 'copy_mails_of_messages_to_administrator', '1', 14),
(5, 'attachment_types', 'png,gif,jpeg,jpg,txt,doc,docx,xls,xlsx,pdf,zip,xml,html,sql,php,psd,mht,ini,ppt,mdb,css', 15);



INSERT INTO `user` (`id`, `signin_id`, `password`, `iv`, `email`, `first_name`) VALUES
(1, 'ADMIN_USERNAME', 'ADMIN_PASSWORD', 'ADMIN_IV', 'ADMIN_EMAIL_ID', 'Admin');


INSERT INTO `user_permission` (`id`, `user_id`, `permission`, `record_type`, `record_id`) VALUES
(1, 1, 1,11,0);

