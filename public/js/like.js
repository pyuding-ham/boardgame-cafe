document.getElementById('btnPostLike').addEventListener('click', function() {
    // 태그에서 post_id 값 추출
    const postId = this.dataset.postId;
    
    // POST 비동기 요청 주소
    const url = '/board-like-toggle';

    fetch(url, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({
            post_id: postId
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.status === 'success') {
            const icon = document.getElementById('likeIcon');
            const countSpan = document.getElementById('likeCount');
            
            // 하트 아이콘 변경
            if (data.is_liked === 1) {
                icon.className = 'bi bi-heart-fill';
            } else {
                icon.className = 'bi bi-heart';
            }
            
            // 최신 좋아요 수 업데이트
            countSpan.textContent = data.like_count;
        } else {
            alert(data.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('오류가 발생했습니다. 다시 시도해 주세요.');
    });
});
