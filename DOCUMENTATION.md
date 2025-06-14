# FishStalker.ai Documentation

## Overview
FishStalker.ai is a web application designed to help users track fishing activities and reports. The application consists of multiple interconnected pages and features a modern, responsive design.

## File Structure
```
├── whats-biting.html      # Main page showing current fishing activity
├── login.html            # User authentication page
├── settings.html         # User settings and preferences
├── contact.html          # Contact information
├── sponsors.html         # Sponsor information
├── css/
│   └── style.css        # Main stylesheet
├── js/
│   └── script.js        # Main JavaScript functionality
└── [PHP files]          # Backend functionality
```

## Core Features

### 1. Navigation System
- Responsive navbar with logo and menu items
- Mobile-friendly hamburger menu
- Navigation links to all major sections
- Smooth scrolling behavior

### 2. User Authentication
- Email-based authentication system
- Local storage for persistent login
- Auto-login functionality
- Session management

### 3. Watchlist System
- Add fishing captains to watchlist
- Required fields:
  - Email
  - Instagram link
  - Name
  - Boat type
  - City
  - Region
- Expandable watchlist cards
- Delete functionality for watchlist entries
- Real-time updates

### 4. Report Generation
- Daily fishing reports
- Past reports viewing
- Report generation by:
  - Individual fish
  - Location
  - Watchlist
- Report display with:
  - Date
  - Location
  - Fish type
  - Weather conditions
  - Additional details

### 5. UI Components

#### Hero Section
- Video background (FishStalkerAI.mp4)
- Overlay content with logo
- Responsive design
- Mobile optimization

#### Cards and Sections
- Main cards with shadow effects
- Expandable sections
- Responsive grid layouts
- Interactive buttons

#### Forms
- Email validation
- Instagram link validation
- Required field checking
- Error handling
- Success messages

### 6. Responsive Design
- Mobile-first approach
- Breakpoints:
  - 1000px: Tablet layout
  - 768px: Small tablet layout
  - 600px: Mobile layout
- Flexible grid systems
- Adaptive typography

### 7. JavaScript Functionality

#### Core Functions
- `DOMContentLoaded` event handling
- Form submission handling
- Watchlist management
- Report generation
- Local storage management

#### Data Management
- Fetch API for backend communication
- JSON data handling
- Error handling
- Loading states

#### UI Interactions
- Expandable cards
- Smooth scrolling
- Form validation
- Dynamic content updates

### 8. CSS Styling

#### Color Scheme
- Primary: #0a3d62 (Dark Blue)
- Secondary: #3c6382 (Medium Blue)
- Accent: #2196F3 (Bright Blue)
- Success: #4CAF50 (Green)
- Warning: #ff9800 (Orange)
- Error: #dc3545 (Red)

#### Typography
- Primary font: 'Segoe UI', 'Arial', sans-serif
- Responsive font sizes
- Clear hierarchy

#### Layout
- Flexbox-based layouts
- CSS Grid for complex layouts
- Responsive containers
- Card-based design

## Dependencies
- Modern web browser with JavaScript enabled
- PHP backend server
- MySQL database (implied by PHP files)

## Known Issues
1. Missing video file (FishStalkerAI.mp4)
2. Missing favicon.png
3. JavaScript hamburger menu functionality not fully implemented
4. Some paths using absolute references instead of relative

## Future Improvements
1. Implement proper video file handling
2. Add favicon
3. Complete hamburger menu functionality
4. Convert absolute paths to relative
5. Add proper error handling for missing resources
6. Implement proper loading states
7. Add user feedback for all actions
8. Implement proper form validation messages 