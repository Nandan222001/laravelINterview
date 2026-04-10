# Mock Interview Feature

## Overview
The Mock Interview feature simulates a realistic interview experience with multi-stage questions, time management, audio recording capabilities, and comprehensive performance analysis.

## Features

### 1. Multi-Stage Interview Flow
- **Behavioral Questions** (20% of time): Focus on past experiences and soft skills
- **Technical Questions** (50% of time): Test Laravel, PHP, and web development knowledge
- **System Design** (30% of time): Evaluate architecture and scalability thinking

### 2. Realistic Time Limits
- Configurable interview duration (30, 45, or 60 minutes)
- Per-stage time allocation based on question type
- Per-question countdown timers
- Visual warnings when time is running low

### 3. Audio Recording (MediaRecorder API)
- Record verbal answers using browser microphone
- Playback recorded answers before moving to next question
- Delete and re-record if needed
- Audio files stored temporarily in browser memory

### 4. Answer Quality Hints
- Real-time keyword detection comparing your answer to model answers
- Shows which key topics you've covered
- Suggests missing important concepts
- Coverage percentage indicator

### 5. Post-Interview Summary
Comprehensive performance report including:
- Total time spent
- Questions answered vs. skipped
- Average time per question
- Stage-by-stage performance breakdown
- Areas covered (topics and difficulty levels)
- Time management visualization
- Detailed question-by-question review

### 6. Downloadable PDF Report (jsPDF)
- Complete interview summary in PDF format
- Includes all questions attempted
- Shows your notes and time spent per question
- Identifies which questions were skipped
- Professional format suitable for self-review

## How to Use

### Starting an Interview
1. Navigate to `pages/mock-interview.html`
2. Configure your interview settings:
   - Select interview duration (30/45/60 minutes)
   - Choose interview stages (Behavioral/Technical/System Design)
   - Select difficulty level (Mixed/Beginner/Intermediate/Advanced/Expert)
   - Enable/disable audio recording

3. Click "Start Interview"

### During the Interview
1. Read each question carefully
2. Use the text area to type your answer notes
3. Optionally, record a verbal answer using the microphone button
4. Watch the timers to manage your time effectively
5. Click "Next Question" when ready, or "Skip Question" if needed
6. End the interview early with "End Interview" button if desired

### After the Interview
1. Review your performance summary
2. Check time management across different stages
3. See which areas you covered
4. Download the PDF report for future reference
5. Start a new interview to practice more

## Technical Details

### Technologies Used
- **HTML5**: Structure and semantics
- **CSS3**: Responsive design with CSS Grid and Flexbox
- **Vanilla JavaScript**: No framework dependencies
- **MediaRecorder API**: Browser-native audio recording
- **jsPDF**: Client-side PDF generation
- **localStorage**: Optional for saving preferences (not implemented yet)

### Browser Compatibility
- Chrome/Edge: Full support (recommended)
- Firefox: Full support
- Safari: Full support (may require permissions for microphone)
- Mobile browsers: Supported with responsive design

### Microphone Permissions
The audio recording feature requires microphone access. The app will:
1. Request permission when you click "Start Recording"
2. Disable the audio feature if permission is denied
3. Work fine without audio if you prefer not to record

## Question Bank

The mock interview includes curated questions across three categories:

### Behavioral (4 questions)
- Project challenges and problem-solving
- Learning new technologies
- Team collaboration
- Performance optimization experiences

### Technical (8 questions)
- Laravel Service Container and DI
- Eloquent ORM vs Query Builder
- Middleware implementation
- Queue system
- Security best practices
- Database transactions
- Events and Listeners
- API rate limiting

### System Design (5 questions)
- URL shortener service
- Social media feed system
- Real-time chat application
- File upload system
- E-commerce order processing

Questions are randomly selected based on your chosen difficulty level.

## Future Enhancements

Potential improvements:
- Integration with actual Laravel interview question bank
- Save interview history to localStorage
- Export audio recordings along with PDF
- AI-powered answer evaluation using NLP
- Video recording capability
- Peer review sharing
- Interview templates for different roles
- Custom question bank upload

## Files

- `pages/mock-interview.html` - Main HTML page
- `assets/css/mock-interview.css` - Custom styles for mock interview
- `assets/js/mock-interview.js` - Application logic and state management

## License

Part of the Laravel Interview Question Bank project.
