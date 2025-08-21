<?php
/*
 * Paste $v3.2 2025/08/21 https://github.com/boxlabss/PASTE
 * demo: https://paste.boxlabs.uk/
 *
 * https://phpaste.sourceforge.io/
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; either version 3
 * of the License, or (at your option) any later version.
 * 
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License in LICENCE for more details.
 */

// Calculate paste size based on $op_content
$paste_size = isset($op_content) ? formatSize(strlen($op_content)) : '0 bytes';
// highlight.php theme switcher - only if $highlighter = 'highlight' in config.php
$showThemeSwitcher = (($highlighter ?? 'geshi') === 'highlight') && (($p_code ?? 'text') !== 'markdown');
$hl_theme_options = [];
$initialTheme     = null;

if ($showThemeSwitcher) {
    $candidatesWeb = [
        'includes/Highlight/styles'
    ];
    $stylesRel = null;
    foreach ($candidatesWeb as $rel) {
        $abs = __DIR__ . '/../../' . $rel;
        if (is_dir($abs)) { $stylesRel = $rel; break; }
    }

    if ($stylesRel) {
        $stylesAbs = __DIR__ . '/../../' . $stylesRel;
        // accept both .css and .min.css; prefer unique themes by basename
        $seen = [];
        foreach (glob($stylesAbs . '/*.css') ?: [] as $f) {
            $file = basename($f);
            $base = preg_replace('~\.min\.css$~', '.css', $file);   // normalize
            $id   = basename($base, '.css');                        // e.g. "atelier-estuary-dark"
            if (isset($seen[$id])) continue;
            $seen[$id] = true;

            $name = ucwords(str_replace(['-', '_'], ' ', $id));
            $hl_theme_options[] = [
                'id'   => $id,
                'name' => $name,
                'href' => rtrim($baseurl ?? '/', '/') . '/' . $stylesRel . '/' . $file,
            ];
        }
        usort($hl_theme_options, fn($a,$b) => strnatcasecmp($a['name'], $b['name']));
    }

	/*
	* if highlighter.php is enabled - 
	* bring the stylesheets from includes/Highlighter/styles 
	* https://github.com/scrivo/highlight.php/tree/master/src/Highlight/styles
	* we can use "?id=1&theme=dracula" OR "?id=1&theme=atelier estuary dark" OR "?id=1&theme=atelier-estuary-dark"
	*/
    if (isset($_GET['theme'])) {
        $g = (string) $_GET['theme'];
        $g = strtolower($g);
        $g = str_replace(['+', ' '], '-', $g);
        $g = str_replace('_', '-', $g);
        $g = preg_replace('~-+~', '-', $g);
        $g = preg_replace('~\.css$~', '', $g);
        $g = preg_replace('~[^a-z0-9.-]~', '', $g);
        $initialTheme = $g;
    }
}

// Main theme below
?>

<!-- Content -->
<div class="container-xl my-4">
    <div class="row">
        <?php if (isset($privatesite) && $privatesite === "on"): ?>
            <!-- Private site: Main content full width, sidebar below -->
            <div class="col-lg-12">
                <?php if (!isset($_SESSION['username'])): ?>
                    <div class="card">
                        <div class="card-body">
                            <div class="alert alert-warning">
                                <?php echo htmlspecialchars($lang['login_required'] ?? 'You must be logged in to view this paste.', ENT_QUOTES, 'UTF-8'); ?>
                                <a href="<?php echo htmlspecialchars($baseurl . '/login.php', ENT_QUOTES, 'UTF-8'); ?>" class="btn btn-primary mt-2">Login</a>
                            </div>
                        </div>
                    </div>
                <?php else: ?>
                    <div class="card">
                        <div class="card-header d-flex justify-content-between align-items-center flex-wrap">
                            <!-- Paste Info: Title, Syntax, Author, Views, Size, and Date -->
                            <div class="paste-info">
                                <h1 class="h3 mb-2"><?php echo ucfirst(htmlspecialchars($p_title ?? 'Untitled')); ?></h1>
                                <div class="meta d-flex flex-wrap gap-2 text-muted small align-items-center">
                                    <span class="badge bg-primary"><?php echo htmlspecialchars(strtoupper($p_code ?? 'TEXT')); ?></span>
                                    <span>
                                        <?php 
                                        $p_member_display = $p_member ?? 'Guest';
                                        if ($p_member_display === 'Guest') {
                                            echo 'Guest';
                                        } else {
                                            $user_link = $mod_rewrite ?? false 
                                                ? htmlspecialchars($baseurl . 'user/' . $p_member_display) 
                                                : htmlspecialchars($baseurl . 'user.php?user=' . $p_member_display);
                                            echo '<a href="' . $user_link . '" class="text-decoration-none">' . htmlspecialchars($p_member_display) . '</a>';
                                        }
                                        ?>
                                    </span>
                                    <span><i class="bi bi-eye me-1"></i><?php echo htmlspecialchars((string) ($p_views ?? 0)); ?> <?php echo htmlspecialchars($lang['views'] ?? 'Views'); ?></span>
                                    <span>Size: <?php echo htmlspecialchars($paste_size); ?></span>
									<span>Posted on: <?php echo htmlspecialchars($p_date ? date('M j, y @ g:i A', strtotime($p_date)) : date('M j, Y, g:i A')); ?></span>
                                </div>
                            </div>
                            <!-- Paste Actions: Buttons -->
							<div class="btn-group btn-group-sm ms-auto" role="group" aria-label="Paste actions">
							  <?php if (!empty($showThemeSwitcher) && !empty($hl_theme_options)): ?>
								<select id="hljs-theme-select"
										class="form-select form-select-sm btn-select order-first me-0"
										title="Code Theme">
								  <?php foreach ($hl_theme_options as $opt): ?>
									<option value="<?php echo htmlspecialchars($opt['id']); ?>">
									  <?php echo htmlspecialchars($opt['name']); ?>
									</option>
								  <?php endforeach; ?>
								</select>

							  <?php endif; ?>  <!-- close the theme-switcher if -->
                                <?php if (($p_code ?? 'text') !== "markdown"): ?>
                                    <button type="button" class="btn btn-outline-secondary toggle-line-numbers" title="Toggle Line Numbers" onclick="togglev()">
                                        <i class="bi bi-list-ol"></i>
                                    </button>
                                <?php endif; ?>
                                <button type="button" class="btn btn-outline-secondary toggle-fullscreen" title="Full Screen" onclick="toggleFullScreen()">
                                    <i class="bi bi-arrows-fullscreen"></i>
                                </button>
                                <button type="button" class="btn btn-outline-secondary copy-clipboard" title="Copy to Clipboard" onclick="copyToClipboard()">
                                    <i class="bi bi-clipboard"></i>
                                </button>
                                <?php
                                $embed_url = getEmbedUrl($paste_id ?? '', $mod_rewrite ?? false, $baseurl ?? '');
                                $embed_code = $paste_id ? '<iframe src="' . htmlspecialchars($embed_url, ENT_QUOTES, 'UTF-8') . '" width="100%" height="400px" frameborder="0" allowfullscreen></iframe>' : '';
                                ?>
                                <button type="button" class="btn btn-outline-secondary embed-tool" title="Embed Paste" onclick="showEmbedCode('<?php echo addslashes(htmlspecialchars($embed_code, ENT_QUOTES, 'UTF-8')); ?>')">
                                    <i class="bi bi-code-square"></i>
                                </button>
                                <a href="<?php echo htmlspecialchars($p_raw ?? ($baseurl . '/raw.php?id=' . ($paste_id ?? ''))); ?>" class="btn btn-outline-secondary" title="Raw Paste">
                                    <i class="bi bi-file-text"></i>
                                </a>
                                <a href="<?php echo htmlspecialchars($p_download ?? ($baseurl . '/download.php?id=' . ($paste_id ?? ''))); ?>" class="btn btn-outline-secondary" title="Download">
                                    <i class="bi bi-file-arrow-down"></i>
                                </a>
                            </div>
                            <div id="notification" class="notification"></div>
                        </div>
                        <div class="card-body">
                            <?php if (isset($error)): ?>
                                <div class="alert alert-danger"><?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></div>
                            <?php else: ?>
                                <div class="code-content" id="code-content"><?php echo $p_content ?? ''; ?></div>
                            <?php endif; ?>
                            <div class="mb-3 position-relative">
                                <p><?php echo htmlspecialchars($lang['rawpaste'] ?? 'Raw Paste'); ?></p>
                                <textarea class="form-control" rows="15" id="code" readonly><?php echo htmlspecialchars($op_content ?? '', ENT_QUOTES, 'UTF-8'); ?></textarea>
                                <div id="line-number-tooltip" class="line-number-tooltip"></div>
                            </div>
                            <div class="btn-group" role="group" aria-label="Fork and Edit actions">
                                <?php if (!isset($_SESSION['username']) && (!isset($privatesite) || $privatesite != "on")): ?>
                                    <a href="#" class="btn btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#signin" title="Login or Register to fork this paste">
                                        <i class="bi bi-git"></i> Fork
                                    </a>
                                    <a href="#" class="btn btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#signin" title="Login or Register to edit this paste">
                                        <i class="bi bi-pencil"></i> Edit
                                    </a>
                                <?php endif; ?>
                            </div>
                            <?php if (isset($_SESSION['username'])): ?>
                                <!-- Paste Edit/Fork Form -->
                                <div class="mt-3">
                                    <div class="card">
                                        <div class="card-header"><?php echo htmlspecialchars($lang['modpaste'] ?? 'Modify Paste'); ?></div>
                                        <div class="card-body">
                                            <form class="form-horizontal" name="mainForm" action="index.php" method="POST">
                                                <div class="row mb-3 g-3">
                                                    <div class="col-sm-4">
                                                        <div class="input-group">
                                                            <span class="input-group-text"><i class="bi bi-fonts"></i></span>
                                                            <input type="text" class="form-control" name="title" placeholder="<?php echo htmlspecialchars($lang['pastetitle'] ?? 'Paste Title'); ?>" value="<?php echo htmlspecialchars(ucfirst($p_title ?? 'Untitled')); ?>">
                                                        </div>
                                                    </div>
                                                    <div class="col-sm-4">
                                                        <select class="form-select" name="format">
                                                            <?php 
                                                            $geshiformats = $geshiformats ?? [];
                                                            $popular_formats = $popular_formats ?? [];
                                                            foreach ($geshiformats as $code => $name) {
                                                                if (in_array($code, $popular_formats)) {
                                                                    $sel = ($p_code ?? 'text') == $code ? 'selected' : '';
                                                                    echo '<option ' . $sel . ' value="' . htmlspecialchars($code) . '">' . htmlspecialchars($name) . '</option>';
                                                                }
                                                            }
                                                            echo '<option value="text">-------------------------------------</option>';
                                                            foreach ($geshiformats as $code => $name) {
                                                                if (!in_array($code, $popular_formats)) {
                                                                    $sel = ($p_code ?? 'text') == $code ? 'selected' : '';
                                                                    echo '<option ' . $sel . ' value="' . htmlspecialchars($code) . '">' . htmlspecialchars($name) . '</option>';
                                                                }
                                                            }
                                                            ?>
                                                        </select>
                                                    </div>
                                                    <div class="col-sm-2 ms-auto">
                                                        <a class="btn btn-secondary highlight-line" href="#" title="Highlight selected lines"><i class="bi bi-text-indent-left"></i> Highlight</a>
                                                    </div>
                                                </div>
                                                <div class="mb-3">
                                                    <textarea class="form-control" rows="15" id="edit-code" name="paste_data" placeholder="helloworld"><?php echo htmlspecialchars($op_content ?? '', ENT_QUOTES, 'UTF-8'); ?></textarea>
                                                </div>
                                                <div class="row mb-3">
                                                    <label class="col-sm-2 col-form-label"><?php echo htmlspecialchars($lang['expiration'] ?? 'Expiration'); ?></label>
                                                    <div class="col-sm-10">
                                                        <select class="form-select" name="paste_expire_date">
                                                            <option value="N" <?php echo ($p_expire_date ?? 'N') == "N" ? 'selected' : ''; ?>>Never</option>
                                                            <option value="self" <?php echo ($p_expire_date ?? 'N') == "self" ? 'selected' : ''; ?>>View Once</option>
                                                            <option value="10M" <?php echo ($p_expire_date ?? 'N') == "10M" ? 'selected' : ''; ?>>10 Minutes</option>
                                                            <option value="1H" <?php echo ($p_expire_date ?? 'N') == "1H" ? 'selected' : ''; ?>>1 Hour</option>
                                                            <option value="1D" <?php echo ($p_expire_date ?? 'N') == "1D" ? 'selected' : ''; ?>>1 Day</option>
                                                            <option value="1W" <?php echo ($p_expire_date ?? 'N') == "1W" ? 'selected' : ''; ?>>1 Week</option>
                                                            <option value="2W" <?php echo ($p_expire_date ?? 'N') == "2W" ? 'selected' : ''; ?>>2 Weeks</option>
                                                            <option value="1M" <?php echo ($p_expire_date ?? 'N') == "1M" ? 'selected' : ''; ?>>1 Month</option>
                                                        </select>
                                                    </div>
                                                </div>
                                                <div class="row mb-3">
                                                    <label class="col-sm-2 col-form-label"><?php echo htmlspecialchars($lang['visibility'] ?? 'Visibility'); ?></label>
                                                    <div class="col-sm-10">
                                                        <select class="form-select" name="visibility">
                                                            <option value="0" <?php echo ($p_visible ?? '0') == "0" ? 'selected' : ''; ?>>Public</option>
                                                            <option value="1" <?php echo ($p_visible ?? '0') == "1" ? 'selected' : ''; ?>>Unlisted</option>
                                                            <option value="2" <?php echo ($p_visible ?? '0') == "2" ? 'selected' : ''; ?>>Private</option>
                                                        </select>
                                                    </div>
                                                </div>
                                                <div class="mb-3">
                                                    <div class="input-group">
                                                        <span class="input-group-text"><i class="bi bi-lock"></i></span>
                                                        <input type="text" class="form-control" name="pass" id="pass" placeholder="<?php echo htmlspecialchars($lang['pwopt'] ?? 'Optional Password'); ?>">
                                                    </div>
                                                </div>
                                                <div class="d-grid gap-2">
                                                    <input type="hidden" name="paste_id" value="<?php echo htmlspecialchars($paste_id ?? ''); ?>" />
                                                    <?php if (isset($_SESSION['username']) && $_SESSION['username'] == ($p_member ?? 'Guest')): ?>
                                                        <input class="btn btn-primary paste-button" type="submit" name="edit" id="edit" value="<?php echo htmlspecialchars($lang['editpaste'] ?? 'Edit Paste'); ?>" />
                                                    <?php endif; ?>
                                                    <input class="btn btn-primary paste-button" type="submit" name="submit" id="submit" value="<?php echo htmlspecialchars($lang['forkpaste'] ?? 'Fork Paste'); ?>" />
                                                </div>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            <?php endif; ?>
                        </div>
                        <!-- Full Screen Modal -->
                        <div class="modal fade" id="fullscreenModal" tabindex="-1" aria-labelledby="fullscreenModalLabel" aria-hidden="true">
                            <div class="modal-dialog modal-fullscreen">
                                <div class="modal-content">
                                    <div class="modal-header">
                                        <h5 class="modal-title" id="fullscreenModalLabel"><?php echo htmlspecialchars($p_title ?? 'Untitled'); ?></h5>
                                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                    </div>
                                    <div class="modal-body">
                                        <div class="code-content" id="fullscreen-code-content"><?php echo $p_content ?? ''; ?></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
            <div class="sidebar-below<?php echo (isset($privatesite) && $privatesite === 'on') ? ' sidebar-below' : ''; ?>">
                <?php require_once('theme/' . ($default_theme ?? 'default') . '/sidebar.php'); ?>
            </div>
        <?php else: ?>
            <!-- Non-private site: Main content and sidebar side by side -->
            <div class="col-lg-10">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center flex-wrap">
                        <!-- Paste Info: Title, Syntax, Author, Views, Size, and Date -->
                        <div class="paste-info">
                            <h1 class="h3 mb-2"><?php echo ucfirst(htmlspecialchars($p_title ?? 'Untitled')); ?></h1>
                            <div class="meta d-flex flex-wrap gap-2 text-muted small align-items-center">
                                <span class="badge bg-primary"><?php echo htmlspecialchars(strtoupper($p_code ?? 'TEXT')); ?></span>
                                <span>
                                    <?php 
                                    $p_member_display = $p_member ?? 'Guest';
                                    if ($p_member_display === 'Guest') {
                                        echo 'Guest';
                                    } else {
                                        $user_link = $mod_rewrite ?? false 
                                            ? htmlspecialchars($baseurl . 'user/' . $p_member_display) 
                                            : htmlspecialchars($baseurl . 'user.php?user=' . $p_member_display);
                                        echo '<a href="' . $user_link . '" class="text-decoration-none">' . htmlspecialchars($p_member_display) . '</a>';
                                    }
                                    ?>
                                </span>
                                <span><i class="bi bi-eye me-1"></i><?php echo htmlspecialchars((string) ($p_views ?? 0)); ?> <?php echo htmlspecialchars($lang['views'] ?? 'Views'); ?></span>
                                <span>Size: <?php echo htmlspecialchars($paste_size); ?></span>
                                <span>Posted on: <?php echo htmlspecialchars($p_date ? date('M j, y @ g:i A', strtotime($p_date)) : date('M j, Y, g:i A')); ?></span>
                            </div>
                        </div>
                        <!-- Paste Actions: Buttons -->
						<div class="btn-group btn-group-sm ms-auto" role="group" aria-label="Paste actions">
						  <?php if (!empty($showThemeSwitcher) && !empty($hl_theme_options)): ?>
							<select id="hljs-theme-select"
									class="form-select form-select-sm btn-select order-first me-0"
									title="Code Theme">
							  <?php foreach ($hl_theme_options as $opt): ?>
								<option value="<?php echo htmlspecialchars($opt['id']); ?>">
								  <?php echo htmlspecialchars($opt['name']); ?>
								</option>
							  <?php endforeach; ?>
							</select>
							<?php endif; ?>  <!-- close the theme-switcher if -->
                            <?php if (($p_code ?? 'text') !== "markdown"): ?>
                                <button type="button" class="btn btn-outline-secondary toggle-line-numbers" title="Toggle Line Numbers" onclick="togglev()">
                                    <i class="bi bi-list-ol"></i>
                                </button>
                            <?php endif; ?>
                            <button type="button" class="btn btn-outline-secondary toggle-fullscreen" title="Full Screen" onclick="toggleFullScreen()">
                                <i class="bi bi-arrows-fullscreen"></i>
                            </button>
                            <button type="button" class="btn btn-outline-secondary copy-clipboard" title="Copy to Clipboard" onclick="copyToClipboard()">
                                <i class="bi bi-clipboard"></i>
                            </button>
                            <?php
                            $embed_url = getEmbedUrl($paste_id ?? '', $mod_rewrite ?? false, $baseurl ?? '');
                            $embed_code = $paste_id ? '<iframe src="' . htmlspecialchars($embed_url, ENT_QUOTES, 'UTF-8') . '" width="100%" height="400px" frameborder="0" allowfullscreen></iframe>' : '';
                            ?>
                            <button type="button" class="btn btn-outline-secondary embed-tool" title="Embed Paste" onclick="showEmbedCode('<?php echo addslashes(htmlspecialchars($embed_code, ENT_QUOTES, 'UTF-8')); ?>')">
                                <i class="bi bi-code-square"></i>
                            </button>
                            <a href="<?php echo htmlspecialchars($p_raw ?? ($baseurl . '/raw.php?id=' . ($paste_id ?? ''))); ?>" class="btn btn-outline-secondary" title="Raw Paste">
                                <i class="bi bi-file-text"></i>
                            </a>
                            <a href="<?php echo htmlspecialchars($p_download ?? ($baseurl . '/download.php?id=' . ($paste_id ?? ''))); ?>" class="btn btn-outline-secondary" title="Download">
                                <i class="bi bi-file-arrow-down"></i>
                            </a>
                            <?php if (!isset($_SESSION['username']) && (!isset($privatesite) || $privatesite != "on") && $disableguest != "on"): ?>
                                <a href="#" class="btn btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#signin" title="Login or Register">
                                    <i class="bi bi-person"></i>
                                </a>
                            <?php endif; ?>
                        </div>
                        <div id="notification" class="notification"></div>
                    </div>
                    <div class="card-body">
                        <?php if (isset($error)): ?>
                            <div class="alert alert-danger"><?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></div>
                        <?php else: ?>
                            <div class="code-content" id="code-content"><?php echo $p_content ?? ''; ?></div>
                        <?php endif; ?>
                        <div class="mb-3 position-relative">
                            <p><?php echo htmlspecialchars($lang['rawpaste'] ?? 'Raw Paste'); ?></p>
                            <textarea class="form-control" rows="15" id="code" readonly><?php echo htmlspecialchars($op_content ?? '', ENT_QUOTES, 'UTF-8'); ?></textarea>
                            <div id="line-number-tooltip" class="line-number-tooltip"></div>
                        </div>
                        <?php if ($disableguest != "on" || isset($_SESSION['username'])): ?>
                            <div class="btn-group" role="group" aria-label="Fork and Edit actions">
                                <?php if (!isset($_SESSION['username']) && (!isset($privatesite) || $privatesite != "on")): ?>
                                    <a href="#" class="btn btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#signin" title="Login or Register to fork this paste">
                                        <i class="bi bi-git"></i> Fork
                                    </a>
                                    <a href="#" class="btn btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#signin" title="Login or Register to edit this paste">
                                        <i class="bi bi-pencil"></i> Edit
                                    </a>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>
                        <?php if (isset($_SESSION['username'])): ?>
                            <!-- Paste Edit/Fork Form -->
                            <div class="mt-3">
                                <div class="card">
                                    <div class="card-header"><?php echo htmlspecialchars($lang['modpaste'] ?? 'Modify Paste'); ?></div>
                                    <div class="card-body">
                                        <form class="form-horizontal" name="mainForm" action="index.php" method="POST">
                                            <div class="row mb-3 g-3">
                                                <div class="col-sm-4">
                                                    <div class="input-group">
                                                        <span class="input-group-text"><i class="bi bi-fonts"></i></span>
                                                        <input type="text" class="form-control" name="title" placeholder="<?php echo htmlspecialchars($lang['pastetitle'] ?? 'Paste Title'); ?>" value="<?php echo htmlspecialchars(ucfirst($p_title ?? 'Untitled')); ?>">
                                                    </div>
                                                </div>
                                                <div class="col-sm-4">
                                                    <select class="form-select" name="format">
                                                        <?php 
                                                        $geshiformats = $geshiformats ?? [];
                                                        $popular_formats = $popular_formats ?? [];
                                                        foreach ($geshiformats as $code => $name) {
                                                            if (in_array($code, $popular_formats)) {
                                                                $sel = ($p_code ?? 'text') == $code ? 'selected' : '';
                                                                echo '<option ' . $sel . ' value="' . htmlspecialchars($code) . '">' . htmlspecialchars($name) . '</option>';
                                                            }
                                                        }
                                                        echo '<option value="text">-------------------------------------</option>';
                                                        foreach ($geshiformats as $code => $name) {
                                                            if (!in_array($code, $popular_formats)) {
                                                                $sel = ($p_code ?? 'text') == $code ? 'selected' : '';
                                                                echo '<option ' . $sel . ' value="' . htmlspecialchars($code) . '">' . htmlspecialchars($name) . '</option>';
                                                            }
                                                        }
                                                        ?>
                                                    </select>
                                                </div>
                                                <div class="col-sm-2 ms-auto">
                                                    <a class="btn btn-secondary highlight-line" href="#" title="Highlight selected lines"><i class="bi bi-text-indent-left"></i> Highlight</a>
                                                </div>
                                            </div>
                                            <div class="mb-3">
                                                <textarea class="form-control" rows="15" id="edit-code" name="paste_data" placeholder="helloworld"><?php echo htmlspecialchars($op_content ?? '', ENT_QUOTES, 'UTF-8'); ?></textarea>
                                            </div>
                                            <div class="row mb-3">
                                                <label class="col-sm-2 col-form-label"><?php echo htmlspecialchars($lang['expiration'] ?? 'Expiration'); ?></label>
                                                <div class="col-sm-10">
                                                    <select class="form-select" name="paste_expire_date">
                                                        <option value="N" <?php echo ($p_expire_date ?? 'N') == "N" ? 'selected' : ''; ?>>Never</option>
                                                        <option value="self" <?php echo ($p_expire_date ?? 'N') == "self" ? 'selected' : ''; ?>>View Once</option>
                                                        <option value="10M" <?php echo ($p_expire_date ?? 'N') == "10M" ? 'selected' : ''; ?>>10 Minutes</option>
                                                        <option value="1H" <?php echo ($p_expire_date ?? 'N') == "1H" ? 'selected' : ''; ?>>1 Hour</option>
                                                        <option value="1D" <?php echo ($p_expire_date ?? 'N') == "1D" ? 'selected' : ''; ?>>1 Day</option>
                                                        <option value="1W" <?php echo ($p_expire_date ?? 'N') == "1W" ? 'selected' : ''; ?>>1 Week</option>
                                                        <option value="2W" <?php echo ($p_expire_date ?? 'N') == "2W" ? 'selected' : ''; ?>>2 Weeks</option>
                                                        <option value="1M" <?php echo ($p_expire_date ?? 'N') == "1M" ? 'selected' : ''; ?>>1 Month</option>
                                                        </select>
                                                    </div>
                                                </div>
                                                <div class="row mb-3">
                                                    <label class="col-sm-2 col-form-label"><?php echo htmlspecialchars($lang['visibility'] ?? 'Visibility'); ?></label>
                                                    <div class="col-sm-10">
                                                        <select class="form-select" name="visibility">
                                                            <option value="0" <?php echo ($p_visible ?? '0') == "0" ? 'selected' : ''; ?>>Public</option>
                                                            <option value="1" <?php echo ($p_visible ?? '0') == "1" ? 'selected' : ''; ?>>Unlisted</option>
                                                            <option value="2" <?php echo ($p_visible ?? '0') == "2" ? 'selected' : ''; ?>>Private</option>
                                                        </select>
                                                    </div>
                                                </div>
                                                <div class="mb-3">
                                                    <div class="input-group">
                                                        <span class="input-group-text"><i class="bi bi-lock"></i></span>
                                                        <input type="text" class="form-control" name="pass" id="pass" placeholder="<?php echo htmlspecialchars($lang['pwopt'] ?? 'Optional Password'); ?>">
                                                    </div>
                                                </div>
                                                <div class="d-grid gap-2">
                                                    <input type="hidden" name="paste_id" value="<?php echo htmlspecialchars($paste_id ?? ''); ?>" />
                                                    <?php if (isset($_SESSION['username']) && $_SESSION['username'] == ($p_member ?? 'Guest')): ?>
                                                        <input class="btn btn-primary paste-button" type="submit" name="edit" id="edit" value="<?php echo htmlspecialchars($lang['editpaste'] ?? 'Edit Paste'); ?>" />
                                                    <?php endif; ?>
                                                    <input class="btn btn-primary paste-button" type="submit" name="submit" id="submit" value="<?php echo htmlspecialchars($lang['forkpaste'] ?? 'Fork Paste'); ?>" />
                                                </div>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            <?php endif; ?>
                        </div>
                        <!-- Full Screen Modal -->
                        <div class="modal fade" id="fullscreenModal" tabindex="-1" aria-labelledby="fullscreenModalLabel" aria-hidden="true">
                            <div class="modal-dialog modal-fullscreen">
                                <div class="modal-content">
                                    <div class="modal-header">
                                        <h5 class="modal-title" id="fullscreenModalLabel"><?php echo htmlspecialchars($p_title ?? 'Untitled'); ?></h5>
										<?php if (!empty($showThemeSwitcher) && !empty($hl_theme_options)): ?>
										  <div class="ms-2" style="min-width:180px">
											<select id="hljs-theme-select" class="form-select form-select-sm" title="Code Theme">
											  <?php foreach ($hl_theme_options as $opt): ?>
												<option value="<?php echo htmlspecialchars($opt['id']); ?>"><?php echo htmlspecialchars($opt['name']); ?></option>
											  <?php endforeach; ?>
											</select>
										  </div>
										<?php endif; ?>  <!-- close the theme-switcher if -->
                                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                    </div>
                                    <div class="modal-body">
                                        <div class="code-content" id="fullscreen-code-content"><?php echo $p_content ?? ''; ?></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-lg-2 mt-4 mt-lg-0">
                    <?php require_once('theme/' . ($default_theme ?? 'default') . '/sidebar.php'); ?>
                </div>
            <?php endif; ?>
        </div>
    </div>