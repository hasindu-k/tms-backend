# Task Management System

## Goal

Develop a full-stack task management application that allows users to create, assign, manage, and track tasks. This project involves building a RESTful API using Laravel, a MySQL database, and a React frontend with Tailwind CSS for styling.

## Description

The Task Management System is designed to streamline task management processes. It provides features for task creation, assignment, tracking, and management, all within an intuitive user interface.

## Technologies Used

-   **Backend**: Laravel
-   **Frontend**: React
-   **Styling**: Tailwind CSS
-   **Database**: MySQL

## Getting Started

### Prerequisites

-   PHP (>=8.0)
-   Composer
-   Node.js (>=14.x)
-   npm or yarn
-   MySQL

### Installation

#### Backend Setup (Laravel)

1. Clone the repository:

    ```bash
    git clone https://github.com/your-username/task-management-system.git
    ```

2. Navigate to the backend directory:

    ```bash
    cd task-management-system/backend
    ```

3. Install PHP dependencies:

    ```bash
    composer install
    composer require laravel/socialite
    ```

4. Set up your environment file:

    ```bash
    cp .env.example .env
    ```

5. Generate an application key:

    ```bash
    php artisan key:generate
    ```

6. Run database migrations:

    ```bash
    php artisan migrate
    ```

7. Start the Laravel development server:
    ```bash
    php artisan serve
    ```

#### Frontend Setup (React)

1. Navigate to the frontend directory:

    ```bash
    cd ../frontend
    ```

2. Install JavaScript dependencies:

    ```bash
    npm install # or yarn install
    ```

3. Start the React development server:
    ```bash
    npm start # or yarn start
    ```

## Usage

-   **API Endpoints**: The Laravel backend provides a RESTful API for managing tasks. Refer to the API documentation for available endpoints and usage details.
-   **Frontend**: The React frontend communicates with the API to perform tasks-related operations.
