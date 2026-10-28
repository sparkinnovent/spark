						<div class="col-lg-6 col-md-6">
							<div class="card card-indigo">
								<div class="card-header with-border">
									<h3 class="card-title"><b>Step 2:</b> Video Presentation Submission</h3>
								</div>
								<div class="card-body">
									<div class="info-box bg-gradient-indigo">
										<span class="info-box-icon"><i class="fa fa-video"></i></span>

										<div class="info-box-content">
											<span class="info-box-text">Presentation Status</span>
											<span class="info-box-number"><?php echo $presentation_status; ?></span>

											<div class="progress">
												<?php
                    /*
                    Progress bar logic:
                    0% NA (Not Available)
                    50% UPLOAD (Video submitted, pending review)
                    100% ACCEPT (Verified)
                    30% REMARK (Issues found)
                    */
                    if ($presentation_status == "NA") {
                    	$submission_progress = 0;
                    } else if ($presentation_status == "UPLOAD") {
                    	$submission_progress = 50;
                    } else if ($presentation_status == "ACCEPT") {
                    	$submission_progress = 100;
                    } else if ($presentation_status == "REMARK") {
                    	$submission_progress = 30;
                    } else {
                    	$submission_progress = 20;
                    }
                    ?>
                    <div class="progress-bar" style="width: <?php echo $submission_progress; ?>%"></div>
                </div>

                <span class="progress-description">
                	<?php
                    /*
                    Remarks based on status:
                    NA - No video submitted.
                    UPLOAD - Our team is reviewing your video submission.
                    ACCEPT - Your video has been approved.
                    REMARK - Issues found in your submission.
                    */
                    if ($presentation_status == "NA") {
                    	$submission_remark = "No video submitted.";
                    } else if ($presentation_status == "UPLOAD") {
                    	$submission_remark = "Our team is reviewing your video submission.";
                    } else if ($presentation_status == "ACCEPT") {
                    	$submission_remark = "Your video has been approved.";
                    } else if ($presentation_status == "REMARK") {
                    	$submission_remark = "After verification, some issues were found.";
                    } else {
                    	$submission_remark = "Status unavailable.";
                    }
                    echo $submission_remark;
                    ?>
                </span>
            </div>
        </div>

        <?php if ($presentation_status == "NA" || $presentation_status == "REMARK") { ?>
        	<form action="presentation.php" method="POST">
        		<small class="form-text text-muted mb-2">
        			Please upload your video presentation via Google Drive. Plese refer to this <a target="_blank" href="https://gdiid.uitm.edu.my/#vidsub">guideline</a>
        		</small>

        		<label for="video_url" class="form-label">Video Submission URL</label>
        		<div class="input-group">
        			<div class="input-group-prepend">
        				<span class="input-group-text"><i class="fas fa-link"></i></span>
        			</div>
        			<input type="url" id="video_url" class="form-control" placeholder="Enter video link here" name="video_url" required />
        		</div>

        		<small class="form-text text-muted mb-2">
        			Ensure that the link is publicly accessible or has appropriate permissions for viewing.
        		</small>
        		<input type="hidden" name="email" value="<?php echo $project_email; ?>">
        		<div class="form-group">
        			<input type="submit" class="btn btn-primary float-right mb-2" name="submit_video" value="Submit" />
        		</div>
        	</form>
        <?php } ?>

        <?php if ($presentation_status == "UPLOAD" || $presentation_status == "ACCEPT") { 
        	$sql_video_submission = "SELECT * FROM project_presentation WHERE project_id = '$project_id' ORDER BY id DESC LIMIT 1";
        	$result_video_submission = $db_handle->run($sql_video_submission);
        	while ($row_video_submission = mysqli_fetch_assoc($result_video_submission)) {
        		$submitted_video_url = $row_video_submission["video_link"];
        		$submission_recorded = $row_video_submission["recorded"];
        		$verified_timestamp = $row_video_submission["verified_timestamp"];
        	}
        	?>
        	<div class="table-responsive">
        		<table class="table table-bordered">
        			<tbody>
        				<tr>
        					<th>Received On</th>
        					<td><i class="fas fa-calendar-alt"></i> <?php echo htmlspecialchars($submission_recorded); ?> <small><b>GMT+8</b></small></td>
        				</tr>
        				<?php if (!empty($verified_timestamp)) { ?>
        					<tr>
        						<th>Verified On</th>
        						<td><i class="fas fa-calendar-alt"></i> <?php echo htmlspecialchars($verified_timestamp); ?> <small><b>GMT+8</b></small></td>
        					</tr>
        				<?php } ?>
        				<tr>
        					<th>Presentation Video file</th>
        					<td>
        						<?php if (!empty($submitted_video_url)) { ?>
        							<a href="<?php echo htmlspecialchars($submitted_video_url); ?>" target="_blank" class="btn btn-block btn-info">
        								<i class="fa fa-link"></i> Open Link
        							</a>
        						<?php } else { ?>
        							No video submitted.
        						<?php } ?>
        					</td>
        				</tr>
                         <?php if (!empty($project_vid_yt)) { ?>
                        <tr>
                            <th>Presentation Video YouTube</th>
                            <td>
                                    <a href="<?php echo htmlspecialchars($project_vid_yt); ?>" target="_blank" class="btn btn-block btn-danger">
                                        <i class="fas fa-video"></i> Open YouTube Video
                                    </a>
                                    <br>
                                    <small class="text-muted">
                                        <i class="fa fa-heart"></i> Please like, share your YouTube video. 
                                        <br>Thank you for joining us in GDIID 2025!
                                    </small>
                            </td>
                        </tr>
                        <?php } ?>
                    </tbody>
                </table>
            </div>
        <?php } ?>

        <?php if ($presentation_status == "REMARK") {
        	$sql_video_remark = "SELECT * FROM project_presentation WHERE project_id = '$project_id' ORDER BY id DESC LIMIT 1";
        	$result_video_remark = $db_handle->run($sql_video_remark);
        	while ($row_video_remark = mysqli_fetch_assoc($result_video_remark)) {
        		$submitted_video_url = $row_video_remark["video_link"];
        		$submission_recorded = $row_video_remark["recorded"];
        		$verified_timestamp = $row_video_remark["verified_timestamp"];
        		$reject_reason = $row_video_remark["reject_reason"];
        		$reject_comment = $row_video_remark["reject_comment"];
        	}
        	?>
        	<div class="table-responsive">
        		<table class="table table-bordered">
        			<tbody>
        				<tr>
        					<th>Submission On</th>
        					<td><?php echo htmlspecialchars($submission_recorded); ?> <small><b>GMT+8</b></small></td>
        				</tr>
                        <tr>
                            <th>Verification On</th>
                            <td><?php echo htmlspecialchars($verified_timestamp); ?> <small><b>GMT+8</b></small></td>
                        </tr>
                        <tr class="table-danger">
                           <th>Rejection Reason</th>
                           <td><?php echo !empty($reject_reason) ? htmlspecialchars($reject_reason) : 'N/A'; ?></td>
                       </tr>
                       <tr class="table-danger">
                           <th>Rejection Comment</th>
                           <td><?php echo !empty($reject_comment) ? nl2br(htmlspecialchars($reject_comment)) : 'No comments provided.'; ?></td>
                       </tr>
                       <tr>
                           <th>Presentation Video</th>
                           <td>
                              <a href="<?php echo htmlspecialchars($submitted_video_url); ?>" target="_blank" class="btn btn-block btn-info">
                                 <i class="fa fa-play"></i> View Video
                             </a>
                         </td>
                     </tr>
                 </tbody>
             </table>
         </div>
     <?php } ?>
 </div>
</div>
</div>