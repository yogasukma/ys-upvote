jQuery(document).ready(function ($) {
    const upvoteButton = $('.ys-upvote-button');
    const downvoteButton = $('.ys-downvote-button');

    // Update button UI based on voting status from PHP
    if (ys_vote_ajax.already_upvoted) {
        upvoteButton.addClass('already-upvoted').prop('disabled', true);
    }
    if (ys_vote_ajax.already_downvoted) {
        downvoteButton.addClass('already-downvoted').prop('disabled', true);
    }

    // Handle vote clicks
    function handleVote(button, voteType) {
        $.ajax({
            type: 'POST',
            url: ys_vote_ajax.ajax_url,
            data: {
                action: 'ys_vote',
                post_id: ys_vote_ajax.post_id,
                vote_type: voteType,
                nonce: ys_vote_ajax.nonce
            },
            success: function (response) {
                if (response.success) {
                    alert(`Thank you for your ${voteType}!`);
                    button.addClass(`already-${voteType}d`).prop('disabled', true);
                } else {
                    alert(response.data.message);
                }
            },
            error: function () {
                alert('Error processing the vote.');
            }
        });
    }

    upvoteButton.on('click', function (e) {
        e.preventDefault();
        handleVote($(this), 'upvote');
    });

    downvoteButton.on('click', function (e) {
        e.preventDefault();
        handleVote($(this), 'downvote');
    });
});

