function toggleAnswer(element) {
            const answer = element.nextElementSibling;
            const isCurrentlyOpen = answer.classList.contains('show');
            
            // Close all other answers
            const allAnswers = document.querySelectorAll('.faq-answer');
            const allQuestions = document.querySelectorAll('.faq-question');
            
            allAnswers.forEach(ans => ans.classList.remove('show'));
            allQuestions.forEach(q => q.classList.remove('active'));
            
            // If this answer wasn't open, open it
            if (!isCurrentlyOpen) {
                answer.classList.add('show');
                element.classList.add('active');
            }
        }