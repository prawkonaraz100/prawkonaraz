export interface LearningProgressMessage {
    state: 'empty' | 'not_started' | 'in_progress' | 'errors' | 'topics_pending' | 'review_due' | 'ready' | 'covered' | 'topic_completed';
    title: string;
    message: string;
    action: 'none' | 'classic' | 'memory' | 'exam';
    icon: 'book' | 'arrow' | 'clipboard' | 'trophy' | 'check' | 'info';
}
