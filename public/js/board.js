document.addEventListener('DOMContentLoaded', function() {
    const searchForm = document.getElementById('searchForm');
    
    if (searchForm) {
        // 검색 조건을 변경했을 때 검색 결과를 1페이지부터 다시 조회
        const resetPageAndSubmit = () => {
            // 검색창(Form)에 submit 신호를 보내 자동으로 새로고침/검색 실행
            searchForm.dispatchEvent(new Event('submit', { cancelable: true }));
        };

        searchForm.addEventListener('submit', function(e) {
            e.preventDefault();

            const targetUrl = searchForm.getAttribute('action');
            const formData = new FormData(searchForm);

            fetch(targetUrl, {
                method: 'POST',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: formData
            })
            .then(response => {
                if (!response.ok) throw new Error('서버 응답 오류: ' + response.status);
                return response.json();
            })
            .then(data => {
                // 주소 변경
                if (data.currentUrl) {
                    history.pushState(null, '', data.currentUrl);
                }

                // 상단 게시글 리스트 교체
                const listContainer = document.getElementById('boardContainer');
                if (listContainer && data.listHtml) {
                    listContainer.innerHTML = data.listHtml;
                }

                // 하단 페이징 블록 교체
                const pagerContainer = document.getElementById('paginationContainer');
                if (pagerContainer && data.paginationHtml) {
                    pagerContainer.innerHTML = data.paginationHtml;
                }

                // 총 게시글 개수 교체
                const totalCountContainer = document.getElementById('totalCountContainer');
                if (totalCountContainer && data.totalCount != null) {
                    totalCountContainer.innerText = data.totalCount; 
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showToast('게시글을 불러오지 못했습니다. 다시 시도해주세요.');
            });
        });

        // 게임소개-카테고리 버튼 클릭 이벤트 
        const tabButtons = document.querySelectorAll('#boardgameTab .nav-link');
        const searchTabInput = document.getElementById('searchTabInput');

        tabButtons.forEach(button => {
            button.addEventListener('click', function() {
                tabButtons.forEach(b => b.classList.remove('active'));
                this.classList.add('active');

                if (searchTabInput) {
                    searchTabInput.value = this.getAttribute('data-tab');
                }

                resetPageAndSubmit();
            });
        });

        // 게임소개-난이도 셀렉트 박스 변경 이벤트
        const difficultySelect = document.getElementById('difficultySelect');
        if (difficultySelect) {
            difficultySelect.addEventListener('change', function() {
                resetPageAndSubmit();
            });
        }

        // 이용후기-지점 셀렉트 박스 변경 이벤트
        const categorySelect = document.getElementById('categorySelect');
        if (categorySelect) {
            categorySelect.addEventListener('change', function() {
                resetPageAndSubmit();
            });
        }
    }

    // 댓글 입력
    const writeCommentForm = document.getElementById('writeCommentForm');
    
    if (writeCommentForm) {
        writeCommentForm.addEventListener('submit', function(e) {
            e.preventDefault();

            // 태그에서 post_id 값 추출
            const postId = this.dataset.postId;

            const formData = new FormData(this);
            formData.append('post_id', postId);

            // POST 비동기 요청 주소
            const url = '/comment-insert';

            fetch(url, {
                method: 'POST',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.status === 'success') {
                    showToast('댓글 작성이 완료되었습니다.');

                    document.getElementById('comment').value = '';
                    document.getElementById('commentCount').style.display = 'none';

                    loadComments();
                } else {
                    showToast(data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showToast('댓글 작성 중 오류가 발생했습니다.');
            });
        });
    }
    
    // 댓글 수정
    const commentContainer = document.getElementById('commentContainer');
    
    if (commentContainer) {
        commentContainer.addEventListener('click', function(e) {
            const editButton = e.target.closest('.btn-comment-edit');
            if (!editButton) {
                return;
            }

            const commentItem = editButton.closest('.comment-item');
            const writeForm = document.getElementById('writeCommentForm');
            const contentDiv = commentItem.querySelector('.comment-content');

            // 댓글 수정 입력 폼은 한 개만 보이도록 함
            const openedItem = commentContainer.querySelector('.comment-item.is-editing');
            if (openedItem && openedItem !== commentItem) {
                const openedCancel = openedItem.querySelector('.btn-edit-cancel');
                if (openedCancel) {
                    openedCancel.click();
                }
            }

            if (commentItem.querySelector('.edit-comment-form')) {
                return;
            }

            const originalText = contentDiv.textContent.trim();

            const editForm = document.createElement('form');
            editForm.className = 'edit-comment-form';

            const textarea = document.createElement('textarea');
            textarea.name = 'comment';
            textarea.rows = 3;
            textarea.maxLength = 3000;
            textarea.value = originalText;

            // 댓글 글자수
            const countBox = document.createElement('div');
            countBox.className = 'edit-comment-count text-body-tertiary small';
            countBox.innerHTML = '<span></span> / 3000';

            const updateEditCount = () => {
                countBox.querySelector('span').textContent = textarea.value.length;
            };
            updateEditCount();
            textarea.addEventListener('input', updateEditCount);

            const buttonWrap = document.createElement('div');
            buttonWrap.className = 'edit-comment-form__actions';

            const submitBtn = document.createElement('button');
            submitBtn.type = 'submit';
            submitBtn.className = 'btn-edit-submit btn btn-link text-secondary p-0 me-2';
            submitBtn.textContent = '저장';

            const cancelBtn = document.createElement('button');
            cancelBtn.type = 'button';
            cancelBtn.className = 'btn-edit-cancel btn btn-link text-secondary p-0';
            cancelBtn.textContent = '취소';

            buttonWrap.append(submitBtn, cancelBtn);
            editForm.append(countBox, textarea, buttonWrap);
            commentItem.classList.add('is-editing');
            contentDiv.replaceChildren(editForm);
            textarea.focus();

            cancelBtn.addEventListener('click', () => {
                commentItem.classList.remove('is-editing');
                contentDiv.textContent = originalText;
            });

            editForm.addEventListener('submit', (event) => {
                event.preventDefault();

                const currentCommentId = commentItem.dataset.commentId;
                const currentPostId = writeForm ? writeForm.dataset.postId : '';

                if (!currentCommentId || !currentPostId) {
                    showToast('댓글 수정 중 오류가 발생했습니다.');
                    return;
                }

                const formData = new FormData(editForm);
                formData.append('post_id', currentPostId);
                formData.append('comment_id', currentCommentId);

                fetch('/comment-edit', {
                    method: 'POST',
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.status === 'success') {
                        showToast('댓글이 수정되었습니다.');
                        loadComments();
                    } else {
                        showToast(data.message);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    showToast('댓글 수정 중 오류가 발생했습니다.');
                });
            });
        });
    }

    // 댓글 목록 재요청
    function loadComments() {
        // 태그에서 post_id 값 추출
        const postId = writeCommentForm.dataset.postId;

        // POST 비동기 요청 주소
        const url = `/comment-list/${postId}`;
        
        fetch(url, {
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(response => response.json())
        .then(data => {
            const commentContainer = document.getElementById('commentContainer');

            if (commentContainer && data.commentListHtml) {
                commentContainer.innerHTML = data.commentListHtml;
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showToast('댓글 목록을 불러오는 중 오류가 발생했습니다.');
        });
    }
});
