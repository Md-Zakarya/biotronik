E-Spandan Project Overview

Project Description
E-Spandan appears to be a medical device management system focused on cardiac implants (like IPGs - Implantable Pulse Generators), with particular emphasis on warranty management, replacement processes, and follow-up care.

Key Components
User Roles
Patients: Users with implanted devices who can request replacements and follow-ups
Service Engineers: Technical staff who handle device installations and replacements
Admins: Oversee the system and approve requests
Distributors: Manage inventory and device distribution
Core Features
Implant Management

Registration of pre and post-Feb 2022 implants
Tracking of warranty periods (typically 3 months from implantation)
Management of device details (serial numbers, models, leads)
Replacement Process

Warranty and paid replacement workflows
Request submission with medical documentation
Admin approval system
Service engineer assignment
Follow-up Care

Appointment scheduling
Payment processing for follow-up visits
Hospital and doctor selection
Security & Authentication

Role-based access control
OTP verification
Secret key implant verification
Technical Architecture
Laravel 11.x backend API
Sanctum for authentication
Spatie/Permission for role management
JSON-based communication
Designed with modern PHP 8.2+
Database Structure
The system uses a relational database with key tables including:

Patients and Users
Implants with detailed specifications
DeviceReplacements for tracking replacement requests
FollowUpRequests for appointment management
Payments for financial transactions