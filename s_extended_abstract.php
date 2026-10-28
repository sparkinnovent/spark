<div class="col-lg-6 col-md-6">
	<div class="card card-indigo">
		<div class="card-header with-border">
			<h3 class="card-title"><b>Step 3:</b> Extended Abstract</h3>
		</div>
		<div class="card-body">
			<div class="info-box bg-gradient-indigo">
				<span class="info-box-icon"><i class="fas fa-file-word"></i></span>

				<div class="info-box-content">
					<span class="info-box-text">Extended Abstract Status</span>
					<span class="info-box-number"><?php echo htmlspecialchars($abstract_status); ?></span>

					<div class="progress">
						<?php
                        // Progress bar logic based on status
						$progress_values = [
							"NA" => [
								"progress" => 0,
								"remark" => "Please upload your extended abstract to begin the review process."
							],
							"UPLOAD" => [
								"progress" => 30,
								"remark" => "Your submission has been received and is currently under preliminary validation by our team."
							],
							"ACCEPT" => [
								"progress" => 30,
								"remark" => "Your abstract has passed validation and will be reviewed by our panel. We will notify you once the review is complete."
							],
							"REVIEW" => [
								"progress" => 50,
								"remark" => "Your extended abstract is currently under review. Please check for reviewer comments when available."
							],
							"CORRECTION" => [
								"progress" => 50,
								"remark" => "Revisions are required based on reviewer feedback. Please make the necessary amendments and resubmit your abstract."
							],
							"REJECT" => [
								"progress" => 10,
								"remark" => "Please resubmit your extended abstract or similarity report according to the remarks provided via email."
							],
							"COMPLETE" => [
								"progress" => 100,
								"remark" => "Your extended abstract has been successfully accepted. Thank you for your submission!"
							]
						];

						$progress = $progress_values[$abstract_status]["progress"] ?? 0;
						$remark = $progress_values[$abstract_status]["remark"] ?? "Status unavailable.";
						?>
						<div class="progress-bar" style="width: <?php echo $progress; ?>%"></div>
					</div>
					<span class="progress-description"><?php echo htmlspecialchars($remark); ?></span>
				</div><!-- /.info-box-content -->
			</div>

			<!-- Show Uploaded File when status is UPLOAD -->
			<?php if ($abstract_status == "UPLOAD" || $abstract_status == "ACCEPT" || $abstract_status == "COMPLETE" || $abstract_status == "REJECT") { 
				$sql_upload = "SELECT * FROM project_extended_abstract WHERE project_id = '$project_id' ORDER BY id DESC LIMIT 1";        
				$result_upload = $db_handle->run($sql_upload);
				$row_file = mysqli_fetch_assoc($result_upload);

				$ex_upload_id = $row_file["id"] ?? "";
				$ex_abstract_file = $row_file["ex_abstract_file"] ?? "";
				$turnitin_file = $row_file["turnitin_file"] ?? "";
				$upload_timestamp = $row_file["upload_timestamp"] ?? "";
				?>
				<div class="table-responsive">
					<table class="table table-bordered">
						<tbody>
							<tr>
								<th>File Submitted</th>
								<td>
									<?php if (!empty($upload_timestamp)) { ?>
										<i class="fas fa-calendar-alt"></i>
										<?php echo htmlspecialchars($upload_timestamp); ?>
										<small><b>GMT+8</b></small>
										
									<?php }?>
								</td>
							</tr>
							<tr>
								<th>Extended Abstract File</th>
								<td>
									<?php if (!empty($ex_abstract_file)) { ?>
										<a href="./download.ea.php?file=<?php echo htmlspecialchars($ex_abstract_file); ?>" target="_blank" class="btn btn-info">
											<i class="fa fa-file-word"></i> View File
										</a>
									<?php } else { ?>
										No file uploaded.
									<?php } ?>
								</td>
							</tr>
							<tr>
								<th>Turnitin File</th>
								<td>
									<?php if (!empty($turnitin_file)) { ?>
										<a href="./download.turnitin.php?file=<?php echo htmlspecialchars($turnitin_file); ?>" target="_blank" class="btn btn-info">
											<i class="fas fa-file-pdf"></i> View File
										</a>
									<?php } else { ?>
										No file uploaded.
									<?php } ?>
								</td>
							</tr>
						</tbody>
					</table>
				</div>
			<?php } ?>

			<!-- Show Review File and Comments when status is REVIEW -->
			<?php if ($abstract_status == "REVIEW" || $abstract_status == "CORRECTION" || $abstract_status == "COMPLETE" ) { 

				$sql_review = "SELECT * FROM project_ex_abstract_review WHERE project_id = '$project_id' ORDER BY id DESC LIMIT 1";        
				$result_review = $db_handle->run($sql_review);
				$row_review = mysqli_fetch_assoc($result_review);

				$review_file = $row_review["review_file"] ?? "";
				//$review_comment = $row_review["review_comment"] ?? "No comments provided.";
				?>
				<div class="table-responsive">
					<table class="table table-bordered">
						<tbody>
							<tr>
								<th>Reviewer File</th>
								<td>
									<?php if (!empty($review_file)) { ?>
										<a href="./download.review.php?file=<?php echo htmlspecialchars($review_file); ?>" target="_blank" class="btn btn-warning">
											<i class="fa fa-file-pdf"></i> View Review File
										</a>
									<?php } else { ?>
										No review file available.
									<?php } ?>
								</td>
							</tr>
							<!-- <tr class="table-warning">
								<th>Reviewer Comment</th>
								<td><?php //echo nl2br(htmlspecialchars($review_comment)); ?></td>
							</tr> -->
						</tbody>
					</table>
				</div>
			<?php } ?>

			<!-- Show Form Only When Status is NULL or REVIEW -->
			<?php if ($abstract_status == NULL || $abstract_status == "REVIEW" || $abstract_status == 'NA' || $abstract_status == 'CORRECTION' || $abstract_status == "REJECT") { ?>
				<form action="ex_abstract.php" method="POST" enctype="multipart/form-data">
					<label>Extended Abstract</label>
					<div class="input-group">
						<div class="input-group-prepend">
							<span class="input-group-text"><i class="fas fa-upload"></i></span>
						</div>
						<input type="file" class="form-control" name="extended_file" accept=".doc,.docx" required>
					</div>
					<small class="form-text text-muted">Only .DOC/.DOCX (MS-Word) files allowed. Max size: 10MB</small>

					<label>Similarity Report</label>
					<div class="input-group">
						<div class="input-group-prepend">
							<span class="input-group-text"><i class="fas fa-upload"></i></span>
						</div>
						<input type="file" accept="application/pdf" class="form-control" name="turitin_file" required>
					</div>
					<small class="form-text text-muted">Only PDF files allowed. Max size: 10MB</small>
					<small class="form-text text-muted">Similarity index should not exceed 30%</small>
					<small class="form-text text-muted">Ouriginal or Turnitin reports are accepted</small>

					<div class="form-group">
						<input type="hidden" name="id" value="<?php echo $project_id;?>">
						<input type="hidden" name="email" value="<?php echo $project_email;?>">
						<input type="submit" class="btn btn-warning float-right" name="submit_ex_abstract" value="Submit" />
					</div>
				</form>
			<?php } ?>
		</div><!-- /.card-body -->
	</div><!-- /.card -->
</div><!-- /.col-lg-6 .col-md-6 -->
