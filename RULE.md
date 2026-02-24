1. SYSTEM OVERVIEW

This project is structured as a multi-platform application:

Backend: PHP 8.2 (RESTful API, JSON response)

Web Application: Vue.js 3 (Composition API)

Mobile Application: React Native 0.82

Communication via REST API (JSON)

Architecture follows separation of concerns and clean code principles. 2. BACKEND ARCHITECTURE (PHP 8.2)
2.1 Architecture Pattern

The backend follows a layered architecture:

Controller → Service → Repository → Database
Controller Layer

Responsibilities:

Handle HTTP requests

Validate input

Call Service layer

Return standardized JSON response

No business logic allowed

Service Layer

Responsibilities:

Contain business logic

Handle transactions if needed

Call one or multiple repositories

limit direct SQL queries

Repository Layer

Responsibilities:

Interact with database

Perform CRUD operations

No business logic

2.2 Response Format Standard

All API responses must follow this structure:

{
"success": true,
"message": "Operation successful",
"data": {}
}

Error format:

{
"success": false,
"message": "Validation failed",
"errors": {}
}

2.4 Naming Conventions
Component Convention
Controller UserController
Service UserService
Repository UserRepository
Model User
Method camelCase
Variables camelCase
DB tables snake_case
API endpoints kebab-case

3.2 Vue Rules

Use Composition API only

Use <script setup>

Separate UI and logic

No complex logic inside components

Use composables for reusable logic

API calls must be placed inside services folder

Component Guidelines

Components:

Only handle UI

Receive data via props

Emit events upward

No direct API calls if complex logic exists

4. MOBILE APPLICATION (React Native 0.82)

4.2 React Native Rules

Functional components only

Use React hooks

Separate UI and business logic

No API calls directly inside UI components

Use custom hooks for reusable logic

5. AUTHENTICATION

Use JWT authentication

Access token:

Web: localStorage

Mobile: Secure Storage

Refresh token strategy recommended

Protect routes with middleware (backend)

Protect routes with navigation guards (frontend)

6. CODE QUALITY RULES

DRY principle

SOLID principle

No duplicated code

One responsibility per function

Maximum 600 lines per file

Meaningful variable names

No magic numbers

12. CLEAN ARCHITECTURE GOAL

The system must:

Be maintainable

Be scalable

Be testable

Be readable

Be production-ready

📘 PROJECT DESCRIPTION
AI-Powered English Teaching Support System

1. Project Overview

This project is an AI-based system for supporting the teaching of subjects in English (e.g., teaching Math in English, History, Geography, etc.), designed to improve classroom effectiveness and personalize student learning.

The system consists of:

Web Application (Vue.js 3): Used by teachers

Mobile Application (React Native 0.82): Used by students

Backend (PHP 8.2): RESTful API + AI integration

The platform leverages OpenAI API to automatically generate learning materials and assessments, while also analyzing student performance to provide intelligent academic evaluations.

2. Target Users
   👩‍🏫 Teacher (Web – Vue.js 3)

Teachers use the web application to:

Create subjects and lessons

Upload lesson content (PDF, DOCX, TXT) or manually import content in any language (Vietnamese, Chinese, English).

Automatically generate:

Presentation slides use English Language

Multiple-choice quizzes use English language

Edit AI-generated questions and answers before publishing

View student results and AI-generated competency evaluations

👨‍🎓 Student (Mobile – React Native 0.82)

Students use the mobile application to:

View assigned lessons

Study lesson slides

Complete multiple-choice quizzes

Submit answers

Receive automatic scoring results

Track learning progress

3. Core Features
   3.1 Lesson Creation with AI

When a teacher creates a lesson:

Teacher uploads lesson content (file) or inputs text manually

Backend sends content to OpenAI API using secure API key

System automatically generates:

Structured slide content

Multiple-choice questions

Correct answers

Teacher can:

Edit questions

Modify answers

Approve before publishing

3.2 Automatic Quiz Scoring

Students submit quiz answers via mobile app

Backend compares answers with correct responses

Score is calculated automatically

Results are stored per:

Lesson

Subject

Student

3.3 AI-Based Student Competency Analysis

The system aggregates:

Scores from multiple quizzes

Performance across lessons

Performance across subjects

This data is analyzed by AI to generate:

Student competency evaluation

Strengths and weaknesses

Learning recommendations

Overall performance summary

Teachers can use this AI-driven insight to:

Adjust teaching strategy

Provide targeted support

Evaluate student learning progress

4. System Architecture
   Backend (PHP 8.2)

RESTful API

Controller → Service → Repository architecture

JWT authentication

OpenAI API integration

Automated grading logic

AI performance analysis module

Web Application (Vue.js 3)

Composition API

Teacher dashboard

Lesson management

Quiz editing interface

Student performance analytics

Mobile Application (React Native 0.82)

Student-focused UI

Lesson viewing

Quiz interface

Real-time scoring feedback

Learning progress tracking

5. Roles and Permissions
   Teacher Role

Create subjects

Create lessons

Upload lesson content

Generate AI slides and quizzes

Edit quiz content

Publish lessons

View student analytics

View AI competency evaluation

Student Role

Access enrolled subjects

View lessons

Complete quizzes

View scores

Track performance history

6. AI Integration

The system integrates with OpenAI API to:

Generate slides from lesson content

Generate multiple-choice questions

Suggest correct answers

Analyze aggregated student performance

Provide competency evaluation and feedback

All AI features are triggered via secure backend processing using environment-stored API keys.

7. Educational Value

This system aims to:

Reduce teacher preparation time

Improve lesson interactivity

Provide instant assessment feedback

Enable data-driven teaching decisions

Personalize student learning through AI analytics

8. Innovation Highlights

AI-powered automatic slide generation

AI-powered quiz generation

Editable AI output (teacher control)

Automatic grading system

AI-based competency evaluation

Multi-platform architecture (Web + Mobile)

Clean backend layered architecture

9. Expected Impact

This system enhances English teaching by:

Automating repetitive tasks

Increasing assessment accuracy

Providing intelligent insights into student performance

Supporting personalized education
