const InterviewApp = (() => {
  const QUESTIONS_BANK = {
    behavioral: [
      {
        id: 1,
        title: "Tell me about a challenging project you worked on",
        text: "Describe a challenging Laravel project you worked on. What were the main technical challenges, and how did you overcome them?",
        difficulty: "intermediate",
        keywords: ["challenge", "problem", "solution", "technical", "team", "overcome", "learned", "result"],
        category: "behavioral"
      },
      {
        id: 2,
        title: "Describe a time when you had to learn a new technology quickly",
        text: "Tell me about a time when you had to learn a new technology or framework quickly for a project. How did you approach it?",
        difficulty: "intermediate",
        keywords: ["learn", "quick", "approach", "resources", "practice", "applied", "documentation", "result"],
        category: "behavioral"
      },
      {
        id: 3,
        title: "How do you handle disagreements with team members",
        text: "Describe a situation where you disagreed with a team member about a technical decision. How did you handle it?",
        difficulty: "intermediate",
        keywords: ["communication", "compromise", "discussion", "perspective", "solution", "team", "respect", "outcome"],
        category: "behavioral"
      },
      {
        id: 4,
        title: "Tell me about a time you optimized application performance",
        text: "Share an experience where you significantly improved the performance of an application. What was your approach?",
        difficulty: "advanced",
        keywords: ["performance", "optimization", "metrics", "bottleneck", "profiling", "improvement", "measured", "impact"],
        category: "behavioral"
      }
    ],
    technical: [
      {
        id: 5,
        title: "Explain Laravel Service Container",
        text: "What is the Laravel Service Container and how does dependency injection work? Provide examples of when you would use it.",
        difficulty: "intermediate",
        keywords: ["container", "dependency", "injection", "binding", "resolution", "singleton", "interface", "implementation"],
        category: "technical"
      },
      {
        id: 6,
        title: "Laravel Eloquent ORM vs Query Builder",
        text: "What are the differences between Laravel's Eloquent ORM and Query Builder? When would you use one over the other?",
        difficulty: "intermediate",
        keywords: ["eloquent", "query builder", "performance", "relationships", "orm", "raw queries", "models", "database"],
        category: "technical"
      },
      {
        id: 7,
        title: "Middleware in Laravel",
        text: "Explain how middleware works in Laravel. How would you create custom middleware for API authentication?",
        difficulty: "intermediate",
        keywords: ["middleware", "request", "response", "pipeline", "authentication", "authorization", "filter", "http"],
        category: "technical"
      },
      {
        id: 8,
        title: "Laravel Queue System",
        text: "Explain Laravel's queue system. How would you implement a background job for sending bulk emails?",
        difficulty: "advanced",
        keywords: ["queue", "jobs", "dispatch", "worker", "background", "async", "redis", "database", "retry"],
        category: "technical"
      },
      {
        id: 9,
        title: "Laravel Security Best Practices",
        text: "What security measures would you implement in a Laravel application handling sensitive user data?",
        difficulty: "advanced",
        keywords: ["csrf", "xss", "sql injection", "encryption", "authentication", "authorization", "validation", "sanitization"],
        category: "technical"
      },
      {
        id: 10,
        title: "Database Transactions in Laravel",
        text: "Explain database transactions in Laravel. When and how would you use them? What happens if a transaction fails?",
        difficulty: "intermediate",
        keywords: ["transaction", "commit", "rollback", "acid", "database", "consistency", "integrity", "closure"],
        category: "technical"
      },
      {
        id: 11,
        title: "Laravel Events and Listeners",
        text: "How do Events and Listeners work in Laravel? Provide a real-world use case for implementing them.",
        difficulty: "advanced",
        keywords: ["events", "listeners", "dispatch", "observer", "decoupling", "subscribers", "async", "broadcast"],
        category: "technical"
      },
      {
        id: 12,
        title: "API Rate Limiting",
        text: "How would you implement rate limiting for a Laravel API? What strategies would you use to prevent abuse?",
        difficulty: "advanced",
        keywords: ["rate limit", "throttle", "middleware", "redis", "cache", "api", "abuse", "quota"],
        category: "technical"
      }
    ],
    systemDesign: [
      {
        id: 13,
        title: "Design a URL Shortener Service",
        text: "Design a URL shortening service like bit.ly using Laravel. Consider scalability, database design, and caching strategies.",
        difficulty: "advanced",
        keywords: ["database", "schema", "scalability", "cache", "redis", "hash", "unique", "redirect", "analytics"],
        category: "system-design"
      },
      {
        id: 14,
        title: "Design a Social Media Feed System",
        text: "Design a scalable social media feed system in Laravel that can handle millions of users. How would you optimize feed generation?",
        difficulty: "expert",
        keywords: ["scale", "feed", "cache", "database", "pagination", "optimization", "redis", "queue", "fanout"],
        category: "system-design"
      },
      {
        id: 15,
        title: "Design a Real-time Chat Application",
        text: "Design a real-time chat application using Laravel. How would you handle message persistence, delivery, and presence?",
        difficulty: "advanced",
        keywords: ["websocket", "broadcast", "pusher", "redis", "database", "messages", "real-time", "presence", "channels"],
        category: "system-design"
      },
      {
        id: 16,
        title: "Design a File Upload System",
        text: "Design a secure and scalable file upload system in Laravel. How would you handle large files, virus scanning, and storage?",
        difficulty: "advanced",
        keywords: ["upload", "s3", "storage", "validation", "chunked", "security", "virus", "queue", "cdn"],
        category: "system-design"
      },
      {
        id: 17,
        title: "Design an E-commerce Order System",
        text: "Design an e-commerce order processing system with Laravel. How would you handle inventory, payments, and order states?",
        difficulty: "expert",
        keywords: ["orders", "inventory", "payment", "transaction", "state machine", "queue", "events", "consistency"],
        category: "system-design"
      }
    ]
  };

  let interviewState = {
    config: {},
    currentStage: 0,
    currentQuestionIndex: 0,
    stages: [],
    questions: [],
    answers: [],
    timers: {
      total: 0,
      stage: 0,
      question: 0
    },
    startTime: null,
    intervals: {},
    mediaRecorder: null,
    audioChunks: [],
    currentRecording: null
  };

  function init() {
    setupEventListeners();
    checkAudioPermissions();
  }

  function setupEventListeners() {
    document.getElementById('start-interview-btn')?.addEventListener('click', startInterview);
    document.getElementById('record-btn')?.addEventListener('click', toggleRecording);
    document.getElementById('delete-recording-btn')?.addEventListener('click', deleteRecording);
    document.getElementById('answer-notes')?.addEventListener('input', updateAnswerHints);
    document.getElementById('skip-btn')?.addEventListener('click', skipQuestion);
    document.getElementById('next-btn')?.addEventListener('click', nextQuestion);
    document.getElementById('end-interview-btn')?.addEventListener('click', endInterview);
    document.getElementById('download-report-btn')?.addEventListener('click', downloadReport);
    document.getElementById('restart-interview-btn')?.addEventListener('click', restartInterview);
  }

  async function checkAudioPermissions() {
    const enableAudio = document.getElementById('enable-audio');
    
    if (navigator.mediaDevices && navigator.mediaDevices.getUserMedia) {
      try {
        const stream = await navigator.mediaDevices.getUserMedia({ audio: true });
        stream.getTracks().forEach(track => track.stop());
        enableAudio.disabled = false;
      } catch (err) {
        console.warn('Microphone access denied or not available:', err);
        enableAudio.checked = false;
        enableAudio.disabled = true;
      }
    } else {
      enableAudio.checked = false;
      enableAudio.disabled = true;
    }
  }

  function startInterview() {
    const duration = parseInt(document.getElementById('interview-duration').value);
    const enableAudio = document.getElementById('enable-audio').checked;
    const difficultyLevel = document.getElementById('difficulty-level').value;
    
    const selectedStages = [];
    if (document.getElementById('stage-behavioral').checked) selectedStages.push('behavioral');
    if (document.getElementById('stage-technical').checked) selectedStages.push('technical');
    if (document.getElementById('stage-system-design').checked) selectedStages.push('systemDesign');

    if (selectedStages.length === 0) {
      alert('Please select at least one interview stage');
      return;
    }

    interviewState.config = {
      duration: duration * 60,
      enableAudio,
      difficultyLevel,
      stages: selectedStages
    };

    prepareInterview();
    showScreen('interview-screen');
    startTimers();
  }

  function prepareInterview() {
    const { stages, duration, difficultyLevel } = interviewState.config;
    
    const stageTimeAllocation = {
      behavioral: 0.2,
      technical: 0.5,
      systemDesign: 0.3
    };

    const totalAllocation = stages.reduce((sum, stage) => {
      return sum + (stageTimeAllocation[stage] || 0);
    }, 0);

    interviewState.stages = stages.map(stage => {
      const allocation = stageTimeAllocation[stage] / totalAllocation;
      const stageTime = Math.floor(duration * allocation);
      const questions = selectQuestionsForStage(stage, difficultyLevel);
      
      return {
        name: stage,
        timeAllocation: stageTime,
        questions: questions,
        questionsAnswered: 0,
        questionsSkipped: 0
      };
    });

    interviewState.questions = interviewState.stages.flatMap(stage => stage.questions);
    interviewState.answers = new Array(interviewState.questions.length).fill(null);
    interviewState.currentStage = 0;
    interviewState.currentQuestionIndex = 0;
    interviewState.startTime = Date.now();

    displayCurrentQuestion();
    updateProgress();
  }

  function selectQuestionsForStage(stage, difficultyLevel) {
    const stageKey = stage === 'systemDesign' ? 'systemDesign' : stage;
    let questions = [...QUESTIONS_BANK[stageKey]];

    if (difficultyLevel !== 'mixed') {
      questions = questions.filter(q => q.difficulty === difficultyLevel);
    }

    questions.sort(() => Math.random() - 0.5);
    
    const numQuestions = stage === 'behavioral' ? 2 : (stage === 'technical' ? 4 : 2);
    return questions.slice(0, Math.min(numQuestions, questions.length));
  }

  function displayCurrentQuestion() {
    const question = interviewState.questions[interviewState.currentQuestionIndex];
    
    if (!question) {
      endInterview();
      return;
    }

    const currentStage = interviewState.stages[interviewState.currentStage];
    
    document.getElementById('current-stage').textContent = formatStageName(currentStage.name);
    document.getElementById('question-counter').textContent = 
      `Question ${interviewState.currentQuestionIndex + 1} of ${interviewState.questions.length}`;
    
    document.getElementById('question-title').textContent = question.title;
    document.getElementById('question-text').textContent = question.text;
    document.getElementById('question-difficulty').textContent = 
      question.difficulty.charAt(0).toUpperCase() + question.difficulty.slice(1);
    document.getElementById('question-difficulty').className = `difficulty-badge ${question.difficulty}`;
    document.getElementById('question-tag').textContent = formatStageName(question.category);

    const existingAnswer = interviewState.answers[interviewState.currentQuestionIndex];
    document.getElementById('answer-notes').value = existingAnswer?.notes || '';
    
    resetRecording();
    updateAnswerHints();
    
    if (interviewState.config.enableAudio) {
      document.getElementById('audio-controls').style.display = 'block';
    } else {
      document.getElementById('audio-controls').style.display = 'none';
    }
  }

  function formatStageName(stage) {
    const names = {
      'behavioral': 'Behavioral',
      'technical': 'Technical',
      'systemDesign': 'System Design',
      'system-design': 'System Design'
    };
    return names[stage] || stage;
  }

  function startTimers() {
    const { duration } = interviewState.config;
    
    interviewState.timers.total = duration;
    updateStageTimer();
    updateQuestionTimer();

    interviewState.intervals.total = setInterval(() => {
      interviewState.timers.total--;
      updateTotalTimer();
      
      if (interviewState.timers.total <= 0) {
        endInterview();
      }
    }, 1000);

    interviewState.intervals.stage = setInterval(() => {
      interviewState.timers.stage--;
      updateStageTimer();
      
      if (interviewState.timers.stage <= 0) {
        moveToNextStage();
      }
    }, 1000);

    interviewState.intervals.question = setInterval(() => {
      interviewState.timers.question--;
      updateQuestionTimer();
      
      if (interviewState.timers.question <= 0) {
        nextQuestion();
      }
    }, 1000);
  }

  function updateStageTimer() {
    const currentStage = interviewState.stages[interviewState.currentStage];
    if (!currentStage) return;
    
    if (interviewState.timers.stage === 0) {
      interviewState.timers.stage = currentStage.timeAllocation;
    }
    
    const minutes = Math.floor(interviewState.timers.stage / 60);
    const seconds = interviewState.timers.stage % 60;
    const timerEl = document.getElementById('stage-timer');
    timerEl.textContent = `${minutes.toString().padStart(2, '0')}:${seconds.toString().padStart(2, '0')}`;
    
    if (interviewState.timers.stage <= 60) {
      timerEl.className = 'timer-value danger';
    } else if (interviewState.timers.stage <= 180) {
      timerEl.className = 'timer-value warning';
    } else {
      timerEl.className = 'timer-value';
    }
  }

  function updateQuestionTimer() {
    const question = interviewState.questions[interviewState.currentQuestionIndex];
    if (!question) return;
    
    if (interviewState.timers.question === 0) {
      const baseTime = question.category === 'system-design' ? 600 : 
                      (question.category === 'technical' ? 300 : 180);
      interviewState.timers.question = baseTime;
    }
    
    const minutes = Math.floor(interviewState.timers.question / 60);
    const seconds = interviewState.timers.question % 60;
    const timerEl = document.getElementById('question-timer');
    timerEl.textContent = `${minutes.toString().padStart(2, '0')}:${seconds.toString().padStart(2, '0')}`;
    
    if (interviewState.timers.question <= 30) {
      timerEl.className = 'timer-value danger';
    } else if (interviewState.timers.question <= 60) {
      timerEl.className = 'timer-value warning';
    } else {
      timerEl.className = 'timer-value';
    }
  }

  function updateTotalTimer() {
    const minutes = Math.floor(interviewState.timers.total / 60);
    const seconds = interviewState.timers.total % 60;
    const timerEl = document.getElementById('total-timer');
    timerEl.textContent = `${minutes.toString().padStart(2, '0')}:${seconds.toString().padStart(2, '0')}`;
    
    if (interviewState.timers.total <= 300) {
      timerEl.className = 'timer-value danger';
    } else if (interviewState.timers.total <= 600) {
      timerEl.className = 'timer-value warning';
    } else {
      timerEl.className = 'timer-value';
    }
  }

  function updateProgress() {
    const progress = ((interviewState.currentQuestionIndex + 1) / interviewState.questions.length) * 100;
    document.getElementById('progress-fill').style.width = `${progress}%`;
  }

  async function toggleRecording() {
    const recordBtn = document.getElementById('record-btn');
    const recordingIndicator = document.getElementById('recording-indicator');
    
    if (!interviewState.mediaRecorder || interviewState.mediaRecorder.state === 'inactive') {
      try {
        const stream = await navigator.mediaDevices.getUserMedia({ audio: true });
        interviewState.mediaRecorder = new MediaRecorder(stream);
        interviewState.audioChunks = [];
        
        interviewState.mediaRecorder.addEventListener('dataavailable', event => {
          interviewState.audioChunks.push(event.data);
        });
        
        interviewState.mediaRecorder.addEventListener('stop', () => {
          const audioBlob = new Blob(interviewState.audioChunks, { type: 'audio/webm' });
          const audioUrl = URL.createObjectURL(audioBlob);
          interviewState.currentRecording = { blob: audioBlob, url: audioUrl };
          
          const audioPlayer = document.getElementById('audio-player');
          audioPlayer.src = audioUrl;
          
          document.getElementById('audio-playback').classList.remove('hidden');
          recordingIndicator.classList.add('hidden');
          recordBtn.classList.remove('recording');
          recordBtn.innerHTML = '<span class="icon">🎤</span><span class="text">Start Recording</span>';
          
          stream.getTracks().forEach(track => track.stop());
        });
        
        interviewState.mediaRecorder.start();
        recordBtn.classList.add('recording');
        recordBtn.innerHTML = '<span class="icon">⏹</span><span class="text">Stop Recording</span>';
        recordingIndicator.classList.remove('hidden');
        
        let recordingTime = 0;
        const recordingInterval = setInterval(() => {
          if (interviewState.mediaRecorder.state === 'recording') {
            recordingTime++;
            const minutes = Math.floor(recordingTime / 60);
            const seconds = recordingTime % 60;
            document.getElementById('recording-time').textContent = 
              `${minutes.toString().padStart(2, '0')}:${seconds.toString().padStart(2, '0')}`;
          } else {
            clearInterval(recordingInterval);
          }
        }, 1000);
        
      } catch (err) {
        console.error('Error accessing microphone:', err);
        alert('Unable to access microphone. Please check your browser permissions.');
      }
    } else if (interviewState.mediaRecorder.state === 'recording') {
      interviewState.mediaRecorder.stop();
    }
  }

  function deleteRecording() {
    if (interviewState.currentRecording) {
      URL.revokeObjectURL(interviewState.currentRecording.url);
      interviewState.currentRecording = null;
    }
    
    document.getElementById('audio-playback').classList.add('hidden');
    document.getElementById('audio-player').src = '';
  }

  function resetRecording() {
    deleteRecording();
    document.getElementById('recording-indicator').classList.add('hidden');
    const recordBtn = document.getElementById('record-btn');
    recordBtn.classList.remove('recording');
    recordBtn.innerHTML = '<span class="icon">🎤</span><span class="text">Start Recording</span>';
  }

  function updateAnswerHints() {
    const notes = document.getElementById('answer-notes').value.toLowerCase();
    const question = interviewState.questions[interviewState.currentQuestionIndex];
    
    if (!question || notes.trim().length < 20) {
      document.getElementById('hints-content').innerHTML = 
        '<p class="hint-placeholder">Start typing or recording to get answer quality hints...</p>';
      return;
    }

    const keywords = question.keywords;
    const foundKeywords = keywords.filter(keyword => notes.includes(keyword.toLowerCase()));
    const missingKeywords = keywords.filter(keyword => !notes.includes(keyword.toLowerCase()));

    let hintsHTML = '<ul class="hints-list">';
    
    foundKeywords.forEach(keyword => {
      hintsHTML += `<li>Mentioned: ${keyword}</li>`;
    });
    
    if (missingKeywords.length > 0 && missingKeywords.length <= 4) {
      missingKeywords.slice(0, 4).forEach(keyword => {
        hintsHTML += `<li class="missing">Consider mentioning: ${keyword}</li>`;
      });
    }
    
    hintsHTML += '</ul>';
    
    const coverage = (foundKeywords.length / keywords.length) * 100;
    if (coverage >= 70) {
      hintsHTML += '<p style="color: var(--success); font-weight: 600; margin-top: 1rem;">✓ Good coverage of key topics!</p>';
    } else if (coverage >= 40) {
      hintsHTML += '<p style="color: var(--warning); font-weight: 600; margin-top: 1rem;">⚠ Consider expanding on key topics</p>';
    }
    
    document.getElementById('hints-content').innerHTML = hintsHTML;
  }

  function saveCurrentAnswer() {
    const notes = document.getElementById('answer-notes').value;
    const question = interviewState.questions[interviewState.currentQuestionIndex];
    
    const timeSpent = question.category === 'system-design' ? 600 - interviewState.timers.question :
                     (question.category === 'technical' ? 300 - interviewState.timers.question : 180 - interviewState.timers.question);
    
    interviewState.answers[interviewState.currentQuestionIndex] = {
      questionId: question.id,
      notes: notes,
      audioRecording: interviewState.currentRecording,
      timeSpent: Math.max(0, timeSpent),
      skipped: false,
      timestamp: Date.now()
    };
  }

  function skipQuestion() {
    const question = interviewState.questions[interviewState.currentQuestionIndex];
    const currentStage = interviewState.stages.find(s => 
      s.questions.some(q => q.id === question.id)
    );
    
    if (currentStage) {
      currentStage.questionsSkipped++;
    }

    interviewState.answers[interviewState.currentQuestionIndex] = {
      questionId: question.id,
      notes: '',
      audioRecording: null,
      timeSpent: 0,
      skipped: true,
      timestamp: Date.now()
    };

    moveToNextQuestion();
  }

  function nextQuestion() {
    saveCurrentAnswer();
    
    const question = interviewState.questions[interviewState.currentQuestionIndex];
    const currentStage = interviewState.stages.find(s => 
      s.questions.some(q => q.id === question.id)
    );
    
    if (currentStage) {
      currentStage.questionsAnswered++;
    }

    moveToNextQuestion();
  }

  function moveToNextQuestion() {
    interviewState.currentQuestionIndex++;
    
    if (interviewState.currentQuestionIndex >= interviewState.questions.length) {
      endInterview();
      return;
    }

    const nextQuestion = interviewState.questions[interviewState.currentQuestionIndex];
    const nextStage = interviewState.stages.find(s => 
      s.questions.some(q => q.id === nextQuestion.id)
    );
    const nextStageIndex = interviewState.stages.indexOf(nextStage);
    
    if (nextStageIndex !== interviewState.currentStage) {
      interviewState.currentStage = nextStageIndex;
      interviewState.timers.stage = 0;
    }

    interviewState.timers.question = 0;
    displayCurrentQuestion();
    updateProgress();
  }

  function moveToNextStage() {
    interviewState.currentStage++;
    
    if (interviewState.currentStage >= interviewState.stages.length) {
      endInterview();
      return;
    }

    interviewState.timers.stage = interviewState.stages[interviewState.currentStage].timeAllocation;
    
    while (interviewState.currentQuestionIndex < interviewState.questions.length) {
      const question = interviewState.questions[interviewState.currentQuestionIndex];
      const stage = interviewState.stages.find(s => s.questions.some(q => q.id === question.id));
      
      if (interviewState.stages.indexOf(stage) === interviewState.currentStage) {
        break;
      }
      
      interviewState.currentQuestionIndex++;
    }

    if (interviewState.currentQuestionIndex >= interviewState.questions.length) {
      endInterview();
      return;
    }

    displayCurrentQuestion();
    updateProgress();
  }

  function endInterview() {
    saveCurrentAnswer();
    stopTimers();
    generateSummary();
    showScreen('summary-screen');
  }

  function stopTimers() {
    Object.values(interviewState.intervals).forEach(interval => {
      if (interval) clearInterval(interval);
    });
    interviewState.intervals = {};
  }

  function generateSummary() {
    const totalTimeSpent = interviewState.config.duration - interviewState.timers.total;
    const questionsAnswered = interviewState.answers.filter(a => a && !a.skipped).length;
    const questionsSkipped = interviewState.answers.filter(a => a && a.skipped).length;
    const avgTimePerQuestion = questionsAnswered > 0 ? totalTimeSpent / questionsAnswered : 0;

    document.getElementById('total-time-stat').textContent = formatTime(totalTimeSpent);
    document.getElementById('questions-answered-stat').textContent = questionsAnswered;
    document.getElementById('questions-skipped-stat').textContent = questionsSkipped;
    document.getElementById('avg-time-stat').textContent = formatTime(Math.floor(avgTimePerQuestion));

    generateStagePerformance();
    generateAreasCovered();
    generateTimeManagement();
    generateQuestionsReview();
  }

  function generateStagePerformance() {
    const container = document.getElementById('stage-performance');
    let html = '';

    interviewState.stages.forEach(stage => {
      const totalQuestions = stage.questions.length;
      const answered = stage.questionsAnswered;
      const skipped = stage.questionsSkipped;
      
      html += `
        <div class="stage-performance-item">
          <div class="stage-name">${formatStageName(stage.name)}</div>
          <div class="stage-stats">
            <span>Answered: ${answered}/${totalQuestions}</span>
            <span>Skipped: ${skipped}</span>
          </div>
        </div>
      `;
    });

    container.innerHTML = html;
  }

  function generateAreasCovered() {
    const container = document.getElementById('areas-covered');
    const areas = new Set();

    interviewState.questions.forEach((question, index) => {
      const answer = interviewState.answers[index];
      if (answer && !answer.skipped) {
        areas.add(question.category);
        areas.add(question.difficulty);
      }
    });

    let html = '<div class="areas-grid">';
    areas.forEach(area => {
      html += `<div class="area-tag">${formatStageName(area)}</div>`;
    });
    html += '</div>';

    container.innerHTML = html;
  }

  function generateTimeManagement() {
    const container = document.getElementById('time-management');
    const totalTime = interviewState.config.duration;
    const timeSpent = totalTime - interviewState.timers.total;

    const stageBreakdown = {};
    interviewState.stages.forEach(stage => {
      stageBreakdown[stage.name] = 0;
    });

    interviewState.questions.forEach((question, index) => {
      const answer = interviewState.answers[index];
      if (answer) {
        stageBreakdown[question.category] += answer.timeSpent;
      }
    });

    let html = '<div class="time-breakdown">';
    
    Object.entries(stageBreakdown).forEach(([stageName, time]) => {
      const percentage = (time / timeSpent) * 100;
      html += `
        <div class="time-item">
          <div class="time-label">${formatStageName(stageName)}</div>
          <div class="time-bar-container">
            <div class="time-bar-fill" style="width: ${percentage}%">
              ${formatTime(time)}
            </div>
          </div>
        </div>
      `;
    });

    html += '</div>';
    container.innerHTML = html;
  }

  function generateQuestionsReview() {
    const container = document.getElementById('questions-review');
    let html = '';

    interviewState.questions.forEach((question, index) => {
      const answer = interviewState.answers[index];
      const status = answer?.skipped ? 'skipped' : 'answered';
      const statusText = answer?.skipped ? 'Skipped' : 'Answered';

      html += `
        <div class="question-review-item">
          <div class="question-review-header">
            <div class="question-review-title">${index + 1}. ${question.title}</div>
            <div class="question-status ${status}">${statusText}</div>
          </div>
          <div class="question-review-meta">
            <span>${formatStageName(question.category)}</span>
            <span>${question.difficulty}</span>
            ${answer ? `<span>Time: ${formatTime(answer.timeSpent)}</span>` : ''}
            ${answer?.audioRecording ? '<span>🎤 Audio recorded</span>' : ''}
          </div>
        </div>
      `;
    });

    container.innerHTML = html;
  }

  function formatTime(seconds) {
    const mins = Math.floor(seconds / 60);
    const secs = seconds % 60;
    return `${mins}:${secs.toString().padStart(2, '0')}`;
  }

  function downloadReport() {
    const { jsPDF } = window.jspdf;
    const doc = new jsPDF();

    let yPos = 20;
    const lineHeight = 7;
    const pageHeight = doc.internal.pageSize.height;

    doc.setFontSize(20);
    doc.text('Mock Interview Report', 20, yPos);
    yPos += lineHeight * 2;

    doc.setFontSize(12);
    doc.text(`Date: ${new Date().toLocaleDateString()}`, 20, yPos);
    yPos += lineHeight;
    doc.text(`Duration: ${interviewState.config.duration / 60} minutes`, 20, yPos);
    yPos += lineHeight * 2;

    doc.setFontSize(16);
    doc.text('Performance Summary', 20, yPos);
    yPos += lineHeight;

    doc.setFontSize(11);
    const totalTimeSpent = interviewState.config.duration - interviewState.timers.total;
    const questionsAnswered = interviewState.answers.filter(a => a && !a.skipped).length;
    const questionsSkipped = interviewState.answers.filter(a => a && a.skipped).length;

    doc.text(`Total Time: ${formatTime(totalTimeSpent)}`, 30, yPos);
    yPos += lineHeight;
    doc.text(`Questions Answered: ${questionsAnswered}`, 30, yPos);
    yPos += lineHeight;
    doc.text(`Questions Skipped: ${questionsSkipped}`, 30, yPos);
    yPos += lineHeight * 2;

    doc.setFontSize(16);
    doc.text('Stage Performance', 20, yPos);
    yPos += lineHeight;

    doc.setFontSize(11);
    interviewState.stages.forEach(stage => {
      if (yPos > pageHeight - 20) {
        doc.addPage();
        yPos = 20;
      }

      doc.text(`${formatStageName(stage.name)}:`, 30, yPos);
      yPos += lineHeight;
      doc.text(`  Answered: ${stage.questionsAnswered}/${stage.questions.length}`, 40, yPos);
      yPos += lineHeight;
      doc.text(`  Skipped: ${stage.questionsSkipped}`, 40, yPos);
      yPos += lineHeight;
    });

    yPos += lineHeight;

    doc.setFontSize(16);
    doc.text('Questions Review', 20, yPos);
    yPos += lineHeight;

    doc.setFontSize(10);
    interviewState.questions.forEach((question, index) => {
      if (yPos > pageHeight - 30) {
        doc.addPage();
        yPos = 20;
      }

      const answer = interviewState.answers[index];
      const status = answer?.skipped ? '[SKIPPED]' : '[ANSWERED]';

      doc.text(`${index + 1}. ${status} ${question.title}`, 30, yPos);
      yPos += lineHeight;
      doc.text(`   Category: ${formatStageName(question.category)} | Difficulty: ${question.difficulty}`, 35, yPos);
      yPos += lineHeight;
      
      if (answer?.timeSpent) {
        doc.text(`   Time spent: ${formatTime(answer.timeSpent)}`, 35, yPos);
        yPos += lineHeight;
      }
      
      if (answer?.notes && answer.notes.trim().length > 0) {
        const lines = doc.splitTextToSize(`   Notes: ${answer.notes}`, 140);
        lines.forEach(line => {
          if (yPos > pageHeight - 20) {
            doc.addPage();
            yPos = 20;
          }
          doc.text(line, 35, yPos);
          yPos += lineHeight;
        });
      }
      
      yPos += lineHeight / 2;
    });

    doc.save(`interview-report-${Date.now()}.pdf`);
  }

  function restartInterview() {
    interviewState = {
      config: {},
      currentStage: 0,
      currentQuestionIndex: 0,
      stages: [],
      questions: [],
      answers: [],
      timers: { total: 0, stage: 0, question: 0 },
      startTime: null,
      intervals: {},
      mediaRecorder: null,
      audioChunks: [],
      currentRecording: null
    };

    document.getElementById('answer-notes').value = '';
    resetRecording();
    showScreen('setup-screen');
  }

  function showScreen(screenId) {
    document.querySelectorAll('.screen').forEach(screen => {
      screen.classList.remove('active');
    });
    document.getElementById(screenId)?.classList.add('active');
  }

  init();

  return {
    startInterview,
    endInterview,
    restartInterview
  };
})();
