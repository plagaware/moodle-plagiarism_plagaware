# moodle-plagiarism_plagaware
Plagiarism Checker Plugin for the Moodle Learn Management System (LMS), powered by the the plagiarism scanner PlagAware.

# Usage and Installation
Please visit the [Moodle PlagAware Installation Article](https://www.plagaware.com/plagiarism-check-moodle) for information on how to install, confiugure and use the PlagAware Moodle plugin (please login as Guest).

# Support
For further support, please contact the [PlagAware Support](https://my.plagaware.com/contact).

# Release Notes

## 2.24 RC3
- Bugfix: Plugin assignment settings get overridden by changes in non-assignemnt course modules
- Change: Note diplayed in plugin assignment settings in case plugin is disabled globally (instead of form)
- Change: Auto submit to PlagAware is disabled in case PlagAware is disabled for an assignment

## 2.24 RC2
- Bugfix: Moodle Plagiarism engine not enabled globaly during plugin installation

## 2.24 RC1
- Bugfix: Compatibility issues with Non-MySQL databases
- Bugfix: Removed PlagAware plugin settings from non-assignment course elements
- Change: Discontinued support for Moodle < 4.1
- Enhancement: Added Error message for failed plagiarism checks
- Enhancement: Various minor cleanups and refactoring

## 2.23
- Enhancement: Ensure compatibility with Moodle 4.1LTS and 4.5LTS
- Enhancement: Support for [file synchronization with PlagAware library](https://www.plagaware.com/de/moodle-bibliothek-synchronisation)
- Enhancement: Implmentation of Moodle Privay API

## 2.10
- Enhancement: Add functionality to restart plagiarism checks
- Enhancement: Add speaking error messages in case of submission errors
- Enhancement: Add icons for easier identification of plagiarism check status
- Enhancement: Handle timeout of plagiarism checks and provide option to restart check
- Bugfix: Removed deprecated function calls for Moodle 3.9.22 and above
- Bugfix: Resloved missing language strings
    
## 2.09
- Bugfix: Duplicate settings in assingment module

## 2.08
- Ensure compatibility with Moodle 4.2x
- Link to interactive plagiarism scan result report rather than static HTML/PDF report

## 2.07
- Ensure compatibility with Moodle 4.1x


