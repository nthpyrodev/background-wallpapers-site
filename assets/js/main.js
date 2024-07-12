
$(document).ready(function() {
    $('.vote-btn').click(function() {
        var submissionId = $(this).data('submission-id');
        var voteType = $(this).data('vote-type');
        var button = $(this);

        $.ajax({
            url: 'vote.php',
            method: 'POST',
            data: { submission_id: submissionId, vote_type: voteType },
            success: function(response) {
                var data = JSON.parse(response);
                button.text(voteType === 'up' ? '👍 ' + data.upvotes : '👎 ' + data.downvotes);
                
                var oppositeType = voteType === 'up' ? 'down' : 'up';
                var oppositeButton = button.siblings('.vote-btn[data-vote-type="' + oppositeType + '"]');
                oppositeButton.text(oppositeType === 'up' ? '👍 ' + data.upvotes : '👎 ' + data.downvotes);
            },
            error: function() {
                alert('An error occurred while voting. Please try again.');
            }
        });
    });
});
