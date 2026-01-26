/**
 * LessonForge Analytics Dashboard
 * 
 * Interactive charts and data visualization for learning insights
 */

const Analytics = {
    // Chart instances
    charts: {},

    // Demo analytics data
    demoData: {
        weeklyProgress: {
            labels: ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'],
            lessonsCompleted: [2, 1, 3, 0, 2, 4, 1],
            timeSpent: [45, 30, 75, 0, 50, 90, 25]
        },
        subjectDistribution: {
            labels: ['Mathematics', 'Science', 'English', 'History', 'Bible Studies'],
            data: [35, 25, 20, 10, 10],
            colors: ['#438a6b', '#3b82f6', '#5aad8a', '#c9b8a5', '#decebf']
        },
        quizScores: {
            labels: ['Fractions', 'Water Cycle', 'Grammar', 'Bible History', 'Geometry'],
            scores: [85, 92, 78, 100, 88]
        },
        monthlyActivity: {
            labels: Array.from({ length: 30 }, (_, i) => i + 1),
            activity: [1, 2, 1, 0, 3, 2, 1, 0, 0, 2, 3, 1, 2, 1, 0, 1, 2, 3, 1, 0, 2, 1, 3, 2, 1, 0, 1, 2, 1, 2]
        },
        skillsRadar: {
            labels: ['Reading', 'Math', 'Science', 'Critical Thinking', 'Scripture', 'Writing'],
            data: [80, 70, 85, 65, 90, 75]
        }
    },

    /**
     * Initialize the analytics dashboard
     */
    init() {
        // Load Chart.js from CDN if not already loaded
        if (typeof Chart === 'undefined') {
            const script = document.createElement('script');
            script.src = 'https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js';
            script.onload = () => this.setupCharts();
            document.head.appendChild(script);
        } else {
            this.setupCharts();
        }
        console.log('📊 Analytics dashboard initialized');
    },

    /**
     * Setup all charts
     */
    setupCharts() {
        // Set default Chart.js options for dark theme
        Chart.defaults.color = 'rgba(255, 255, 255, 0.7)';
        Chart.defaults.borderColor = 'rgba(255, 255, 255, 0.1)';
        Chart.defaults.font.family = "'Inter', sans-serif";
    },

    /**
     * Render the analytics dashboard
     */
    renderDashboard() {
        return `
            <div class="analytics-dashboard">
                <!-- Summary Cards -->
                <div class="analytics-summary">
                    <div class="analytics-card">
                        <div class="analytics-card-icon" style="background: rgba(67, 138, 107, 0.2); color: #5aad8a;">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M12 2L2 7l10 5 10-5-10-5z"/>
                                <path d="M2 17l10 5 10-5"/>
                            </svg>
                        </div>
                        <div class="analytics-card-content">
                            <span class="analytics-value" id="total-lessons">12</span>
                            <span class="analytics-label">Lessons Taken</span>
                            <span class="analytics-change positive">+3 this week</span>
                        </div>
                    </div>
                    <div class="analytics-card">
                        <div class="analytics-card-icon" style="background: rgba(16, 185, 129, 0.2); color: #10b981;">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <polyline points="22 12 18 12 15 21 9 3 6 12 2 12"/>
                            </svg>
                        </div>
                        <div class="analytics-card-content">
                            <span class="analytics-value" id="avg-score">87%</span>
                            <span class="analytics-label">Average Score</span>
                            <span class="analytics-change positive">+5% improvement</span>
                        </div>
                    </div>
                    <div class="analytics-card">
                        <div class="analytics-card-icon" style="background: rgba(59, 130, 246, 0.2); color: #3b82f6;">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <circle cx="12" cy="12" r="10"/>
                                <polyline points="12 6 12 12 16 14"/>
                            </svg>
                        </div>
                        <div class="analytics-card-content">
                            <span class="analytics-value" id="time-spent">4h 32m</span>
                            <span class="analytics-label">Time Learning</span>
                            <span class="analytics-change neutral">This week</span>
                        </div>
                    </div>
                    <div class="analytics-card">
                        <div class="analytics-card-icon" style="background: rgba(245, 158, 11, 0.2); color: #f59e0b;">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/>
                            </svg>
                        </div>
                        <div class="analytics-card-content">
                            <span class="analytics-value" id="badges-earned">5</span>
                            <span class="analytics-label">Badges Earned</span>
                            <span class="analytics-change positive">+2 new!</span>
                        </div>
                    </div>
                </div>

                <!-- Charts Row 1 -->
                <div class="charts-row">
                    <div class="chart-container wide">
                        <h3>Weekly Progress</h3>
                        <canvas id="weekly-progress-chart"></canvas>
                    </div>
                    <div class="chart-container">
                        <h3>Subject Distribution</h3>
                        <canvas id="subject-distribution-chart"></canvas>
                    </div>
                </div>

                <!-- Charts Row 2 -->
                <div class="charts-row">
                    <div class="chart-container">
                        <h3>Quiz Performance</h3>
                        <canvas id="quiz-scores-chart"></canvas>
                    </div>
                    <div class="chart-container">
                        <h3>Skill Assessment</h3>
                        <canvas id="skills-radar-chart"></canvas>
                    </div>
                </div>

                <!-- Activity Heatmap -->
                <div class="chart-container full-width">
                    <h3>Monthly Activity</h3>
                    <div class="activity-heatmap" id="activity-heatmap"></div>
                    <div class="heatmap-legend">
                        <span>Less</span>
                        <div class="heatmap-scale">
                            <div class="scale-0"></div>
                            <div class="scale-1"></div>
                            <div class="scale-2"></div>
                            <div class="scale-3"></div>
                        </div>
                        <span>More</span>
                    </div>
                </div>
            </div>
        `;
    },

    /**
     * Initialize charts after DOM is ready
     */
    initCharts() {
        setTimeout(() => {
            this.createWeeklyProgressChart();
            this.createSubjectDistributionChart();
            this.createQuizScoresChart();
            this.createSkillsRadarChart();
            this.createActivityHeatmap();
        }, 100);
    },

    /**
     * Weekly Progress Line Chart
     */
    createWeeklyProgressChart() {
        const ctx = document.getElementById('weekly-progress-chart');
        if (!ctx) return;

        this.charts.weeklyProgress = new Chart(ctx, {
            type: 'line',
            data: {
                labels: this.demoData.weeklyProgress.labels,
                datasets: [
                    {
                        label: 'Lessons Completed',
                        data: this.demoData.weeklyProgress.lessonsCompleted,
                        borderColor: '#438a6b',
                        backgroundColor: 'rgba(67, 138, 107, 0.1)',
                        fill: true,
                        tension: 0.4,
                        pointBackgroundColor: '#438a6b',
                        pointBorderColor: '#fff',
                        pointBorderWidth: 2,
                        pointRadius: 5
                    },
                    {
                        label: 'Minutes Studied',
                        data: this.demoData.weeklyProgress.timeSpent,
                        borderColor: '#3b82f6',
                        backgroundColor: 'rgba(59, 130, 246, 0.1)',
                        fill: true,
                        tension: 0.4,
                        pointBackgroundColor: '#3b82f6',
                        pointBorderColor: '#fff',
                        pointBorderWidth: 2,
                        pointRadius: 5,
                        yAxisID: 'y1'
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                interaction: {
                    mode: 'index',
                    intersect: false
                },
                plugins: {
                    legend: {
                        position: 'top'
                    }
                },
                scales: {
                    y: {
                        type: 'linear',
                        display: true,
                        position: 'left',
                        title: {
                            display: true,
                            text: 'Lessons'
                        }
                    },
                    y1: {
                        type: 'linear',
                        display: true,
                        position: 'right',
                        title: {
                            display: true,
                            text: 'Minutes'
                        },
                        grid: {
                            drawOnChartArea: false
                        }
                    }
                }
            }
        });
    },

    /**
     * Subject Distribution Doughnut Chart
     */
    createSubjectDistributionChart() {
        const ctx = document.getElementById('subject-distribution-chart');
        if (!ctx) return;

        this.charts.subjectDistribution = new Chart(ctx, {
            type: 'doughnut',
            data: {
                labels: this.demoData.subjectDistribution.labels,
                datasets: [{
                    data: this.demoData.subjectDistribution.data,
                    backgroundColor: this.demoData.subjectDistribution.colors,
                    borderWidth: 0,
                    hoverOffset: 10
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                cutout: '65%',
                plugins: {
                    legend: {
                        position: 'right',
                        labels: {
                            padding: 15,
                            usePointStyle: true,
                            pointStyle: 'circle'
                        }
                    }
                }
            }
        });
    },

    /**
     * Quiz Scores Bar Chart
     */
    createQuizScoresChart() {
        const ctx = document.getElementById('quiz-scores-chart');
        if (!ctx) return;

        const scores = this.demoData.quizScores.scores;
        const colors = scores.map(score => {
            if (score >= 90) return '#10b981';
            if (score >= 70) return '#f59e0b';
            return '#ef4444';
        });

        this.charts.quizScores = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: this.demoData.quizScores.labels,
                datasets: [{
                    label: 'Score',
                    data: scores,
                    backgroundColor: colors,
                    borderRadius: 8,
                    borderSkipped: false
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        max: 100,
                        ticks: {
                            callback: value => value + '%'
                        }
                    }
                }
            }
        });
    },

    /**
     * Skills Radar Chart
     */
    createSkillsRadarChart() {
        const ctx = document.getElementById('skills-radar-chart');
        if (!ctx) return;

        this.charts.skillsRadar = new Chart(ctx, {
            type: 'radar',
            data: {
                labels: this.demoData.skillsRadar.labels,
                datasets: [{
                    label: 'Your Skills',
                    data: this.demoData.skillsRadar.data,
                    fill: true,
                    backgroundColor: 'rgba(67, 138, 107, 0.2)',
                    borderColor: '#438a6b',
                    pointBackgroundColor: '#438a6b',
                    pointBorderColor: '#fff',
                    pointHoverBackgroundColor: '#fff',
                    pointHoverBorderColor: '#438a6b',
                    pointRadius: 5
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    }
                },
                scales: {
                    r: {
                        beginAtZero: true,
                        max: 100,
                        ticks: {
                            stepSize: 20,
                            display: false
                        },
                        pointLabels: {
                            font: {
                                size: 11
                            }
                        }
                    }
                }
            }
        });
    },

    /**
     * Activity Heatmap (CSS-based)
     */
    createActivityHeatmap() {
        const container = document.getElementById('activity-heatmap');
        if (!container) return;

        const activity = this.demoData.monthlyActivity.activity;
        const maxActivity = Math.max(...activity);

        let html = '';
        for (let i = 0; i < activity.length; i++) {
            const level = maxActivity > 0 ? Math.ceil((activity[i] / maxActivity) * 3) : 0;
            const day = i + 1;
            const count = activity[i];
            html += `<div class="heatmap-day level-${level}" 
                          data-day="Day ${day}" 
                          data-count="${count} activities"></div>`;
        }

        container.innerHTML = html;

        // Add event listeners for custom tooltip
        this.setupHeatmapTooltips(container);
    },

    /**
     * Setup tooltip interactivity
     */
    setupHeatmapTooltips(container) {
        // Create tooltip element if not exists
        let tooltip = document.getElementById('analytics-tooltip');
        if (!tooltip) {
            tooltip = document.createElement('div');
            tooltip.id = 'analytics-tooltip';
            tooltip.className = 'analytics-tooltip';
            document.body.appendChild(tooltip);
        }

        // Mouse over
        container.addEventListener('mouseover', (e) => {
            if (e.target.classList.contains('heatmap-day')) {
                const day = e.target.getAttribute('data-day');
                const count = e.target.getAttribute('data-count');

                tooltip.innerHTML = `<strong>${day}</strong><br>${count}`;
                tooltip.style.opacity = '1';
                tooltip.style.visibility = 'visible';
            }
        });

        // Mouse move (follow cursor)
        container.addEventListener('mousemove', (e) => {
            if (e.target.classList.contains('heatmap-day')) {
                // Position above cursor
                const x = e.pageX;
                const y = e.pageY - 10;

                tooltip.style.left = `${x}px`;
                tooltip.style.top = `${y}px`;
                tooltip.style.transform = 'translate(-50%, -100%)';
            }
        });

        // Mouse leave
        container.addEventListener('mouseout', (e) => {
            if (e.target.classList.contains('heatmap-day')) {
                tooltip.style.opacity = '0';
                tooltip.style.visibility = 'hidden';
            }
        });
    },

    /**
     * Destroy all charts
     */
    destroyCharts() {
        Object.values(this.charts).forEach(chart => {
            if (chart && typeof chart.destroy === 'function') {
                chart.destroy();
            }
        });
        this.charts = {};
    },

    /**
     * Generate report summary
     */
    generateReportSummary() {
        const data = this.demoData;
        const totalLessons = data.weeklyProgress.lessonsCompleted.reduce((a, b) => a + b, 0);
        const avgScore = Math.round(data.quizScores.scores.reduce((a, b) => a + b, 0) / data.quizScores.scores.length);
        const totalTime = data.weeklyProgress.timeSpent.reduce((a, b) => a + b, 0);

        return {
            lessonsThisWeek: totalLessons,
            averageScore: avgScore,
            totalMinutes: totalTime,
            topSubject: 'Mathematics',
            strengths: ['Scripture', 'Science'],
            areasToImprove: ['Critical Thinking']
        };
    }
};

// Export for global access
window.Analytics = Analytics;
