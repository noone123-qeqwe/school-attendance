<?php $__env->startSection('portal-title', 'Dashboard'); ?>

<?php $__env->startSection('content'); ?>
<style>
    /* â”€â”€ TEACHER DASHBOARD MOBILE â”€â”€ */
    @media (max-width: 768px) {
        .col-lg-8, .col-lg-4 { width: 100% !important; flex: none !important; max-width: 100% !important; }
        .stat-card-premium { padding: 14px !important; min-height: 90px !important; }
        .stat-val-premium { font-size: 1.5rem !important; }
        .class-item { padding: 12px 14px !important; }
        .d-flex.gap-3.flex-wrap { overflow-x: auto !important; flex-wrap: nowrap !important; padding-bottom: 8px; }
        .d-flex.gap-3.flex-wrap > div { min-width: 170px !important; flex-shrink: 0; }
        .clock-text { font-size: 1.6rem !important; }
    }
</style>


<div class="teacher-mobile-header mobile-dash-header d-md-none ent-fade-up" style="display:flex; align-items:center; justify-content:space-between; margin-bottom:16px;">
    <div>
        <div class="mobile-dash-title" style="font-size:1.45rem; font-weight:800; line-height:1.2;">
            My Dashboard
        </div>
        <div class="mobile-dash-subtitle" style="font-size:0.85rem; color:var(--text-secondary); margin-top:4px;">
            <?php echo e(now()->format('l, F j')); ?>

        </div>
    </div>
    <div style="text-align:right;">
        <div id="teacherClockMobile" style="font-size:1.1rem;font-weight:800;color:var(--gold);"><?php echo e(now()->format('h:i A')); ?></div>
        <div class="mobile-dash-date" style="font-size:0.75rem; color:var(--text-muted);">Teacher Portal</div>
    </div>
</div>


<?php if(isset($pendingExcuses) && $pendingExcuses > 0): ?>
<div class="ent-alert gold ent-fade-up ent-delay-1" style="margin-bottom:20px;">
    <div class="ent-alert-icon">
        <i class="bi bi-file-earmark-text-fill"></i>
    </div>
    <div class="ent-alert-body">
        <div class="ent-alert-title" style="color:#f3e7cd;">Action Required: <?php echo e($pendingExcuses); ?> Excuse<?php echo e($pendingExcuses > 1 ? 's' : ''); ?> Pending</div>
        <div class="ent-alert-text" style="color:var(--ent-text-secondary);">Students have submitted excuse letters that require your immediate attention.</div>
    </div>
    <a href="<?php echo e(route('teacher.excuse.reviews')); ?>" class="ent-btn ent-btn-primary" style="flex-shrink:0;">
        <i class="bi bi-eye"></i> Review Now
    </a>
</div>
<?php endif; ?>


<div class="teacher-quick-bar ent-section ent-fade-up ent-delay-1 d-none d-md-flex" style="align-items:center;justify-content:space-between;flex-wrap:wrap;gap:20px;margin-bottom:24px;padding:24px 28px;">
    <div style="display:flex;align-items:center;gap:20px;">
        <div style="width:56px;height:56px;border-radius:var(--ent-radius-md);background:rgba(207,164,111,0.12);border:1px solid rgba(207,164,111,0.2);display:flex;align-items:center;justify-content:center;color:#cfa46f;font-size:1.6rem;">
            <i class="bi bi-clock-fill"></i>
        </div>
        <div>
            <div style="font-size:0.72rem;font-weight:600;color:var(--ent-text-muted);text-transform:uppercase;letter-spacing:0.06em;margin-bottom:2px;">Current Session Time</div>
            <div id="teacherClock" class="clock-text" style="font-size:2.2rem;font-weight:800;line-height:1.1;letter-spacing:-1px;color:var(--ent-text);">
                <?php echo e(now()->format('h:i:s A')); ?>

            </div>
            <div style="font-size:0.85rem;color:var(--ent-text-secondary);margin-top:2px;font-weight:500;">
                <?php echo e(now()->format('l, F j, Y')); ?>

            </div>
        </div>
    </div>
    <div style="display:flex;gap:12px;flex-wrap:wrap;align-items:center;">
        <a href="<?php echo e(route('teacher.reports.pdf')); ?>" target="_blank" class="ent-btn ent-btn-secondary">
            <i class="bi bi-file-earmark-arrow-down-fill" style="color:#cfa46f;"></i> Export Report
        </a>
        <button type="button" class="ent-btn ent-btn-secondary" style="color:#fca5a5;border-color:rgba(239,68,68,0.2);" onclick="openLeaveDrawer()">
            <i class="bi bi-calendar-x-fill"></i> Request Leave
        </button>

        <?php
            $nextClass = $todayClasses->first(function($c) {
                $sched = $c->schedules->first();
                if (!$sched) return false;
                $end = \Carbon\Carbon::today()->setTimeFromTimeString($sched->end_time);
                return now()->lessThan($end) && !($c->has_attendance_today ?? false);
            });
        ?>
        <?php if($nextClass): ?>
        <a href="<?php echo e(route('teacher.attendance', $nextClass->id)); ?>" class="ent-btn ent-btn-primary" style="background:linear-gradient(135deg,#16a34a,#15803d);border-color:rgba(74,222,128,0.3);">
            <i class="bi bi-qr-code-scan"></i> Start: <?php echo e($nextClass->name); ?>

        </a>
        <?php else: ?>
        <div style="display:flex;align-items:center;gap:10px;background:rgba(34,197,94,0.08);border:1px solid rgba(34,197,94,0.2);padding:10px 20px;border-radius:var(--ent-radius-sm);">
            <div style="width:28px;height:28px;background:linear-gradient(135deg,#22c55e,#16a34a);border-radius:50%;display:flex;align-items:center;justify-content:center;color:white;font-size:0.9rem;">
                <i class="bi bi-check2-all"></i>
            </div>
            <div>
                <div style="color:#4ade80;font-weight:700;font-size:0.85rem;">All caught up!</div>
                <div style="color:var(--ent-text-muted);font-size:0.75rem;">No more classes today.</div>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>


<div class="teacher-mobile-actions ent-mobile-actions d-md-none ent-fade-up ent-delay-1">
    <a href="<?php echo e(route('teacher.reports.pdf')); ?>" target="_blank" class="ent-mobile-action-btn">
        <i class="bi bi-file-earmark-arrow-down-fill"></i> Export
    </a>
    <button type="button" class="ent-mobile-action-btn" onclick="openLeaveDrawer()">
        <i class="bi bi-calendar-x-fill"></i> Leave
    </button>

    <a href="<?php echo e(route('teacher.classroom.index')); ?>" class="ent-mobile-action-btn">
        <i class="bi bi-journal-album"></i> Classes
    </a>
    <a href="<?php echo e(route('teacher.absent')); ?>" class="ent-mobile-action-btn">
        <i class="bi bi-person-x-fill"></i> Absent
    </a>
</div>


<div class="ent-grid ent-grid-4 ent-mb-lg skel-kpi-placeholder" id="skelKpis">
    <?php if (isset($component)) { $__componentOriginal31de594fa8b31c89482b92f93a0b9eeb = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal31de594fa8b31c89482b92f93a0b9eeb = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.skeleton','data' => ['type' => 'kpi','count' => 4]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('skeleton'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['type' => 'kpi','count' => 4]); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal31de594fa8b31c89482b92f93a0b9eeb)): ?>
<?php $attributes = $__attributesOriginal31de594fa8b31c89482b92f93a0b9eeb; ?>
<?php unset($__attributesOriginal31de594fa8b31c89482b92f93a0b9eeb); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal31de594fa8b31c89482b92f93a0b9eeb)): ?>
<?php $component = $__componentOriginal31de594fa8b31c89482b92f93a0b9eeb; ?>
<?php unset($__componentOriginal31de594fa8b31c89482b92f93a0b9eeb); ?>
<?php endif; ?>
</div>


<div class="ent-grid ent-grid-4 ent-mb-lg ent-fade-up ent-delay-2" id="realKpis" style="display:none;">
    <?php if (isset($component)) { $__componentOriginal53747ceb358d30c0105769f8471417f6 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal53747ceb358d30c0105769f8471417f6 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.card','data' => ['type' => 'kpi','accent' => 'success','icon' => 'bi bi-person-check-fill','label' => 'Present Today','value' => ''.e($totalPresent).'']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('card'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['type' => 'kpi','accent' => 'success','icon' => 'bi bi-person-check-fill','label' => 'Present Today','value' => ''.e($totalPresent).'']); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal53747ceb358d30c0105769f8471417f6)): ?>
<?php $attributes = $__attributesOriginal53747ceb358d30c0105769f8471417f6; ?>
<?php unset($__attributesOriginal53747ceb358d30c0105769f8471417f6); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal53747ceb358d30c0105769f8471417f6)): ?>
<?php $component = $__componentOriginal53747ceb358d30c0105769f8471417f6; ?>
<?php unset($__componentOriginal53747ceb358d30c0105769f8471417f6); ?>
<?php endif; ?>
    <?php if (isset($component)) { $__componentOriginal53747ceb358d30c0105769f8471417f6 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal53747ceb358d30c0105769f8471417f6 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.card','data' => ['type' => 'kpi','accent' => 'warning','icon' => 'bi bi-hourglass-split','label' => 'Late Today','value' => ''.e($totalLate).'']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('card'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['type' => 'kpi','accent' => 'warning','icon' => 'bi bi-hourglass-split','label' => 'Late Today','value' => ''.e($totalLate).'']); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal53747ceb358d30c0105769f8471417f6)): ?>
<?php $attributes = $__attributesOriginal53747ceb358d30c0105769f8471417f6; ?>
<?php unset($__attributesOriginal53747ceb358d30c0105769f8471417f6); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal53747ceb358d30c0105769f8471417f6)): ?>
<?php $component = $__componentOriginal53747ceb358d30c0105769f8471417f6; ?>
<?php unset($__componentOriginal53747ceb358d30c0105769f8471417f6); ?>
<?php endif; ?>
    <?php if (isset($component)) { $__componentOriginal53747ceb358d30c0105769f8471417f6 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal53747ceb358d30c0105769f8471417f6 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.card','data' => ['type' => 'kpi','accent' => 'danger','icon' => 'bi bi-person-x-fill','label' => 'Absent Today','value' => ''.e($totalAbsent).'']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('card'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['type' => 'kpi','accent' => 'danger','icon' => 'bi bi-person-x-fill','label' => 'Absent Today','value' => ''.e($totalAbsent).'']); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal53747ceb358d30c0105769f8471417f6)): ?>
<?php $attributes = $__attributesOriginal53747ceb358d30c0105769f8471417f6; ?>
<?php unset($__attributesOriginal53747ceb358d30c0105769f8471417f6); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal53747ceb358d30c0105769f8471417f6)): ?>
<?php $component = $__componentOriginal53747ceb358d30c0105769f8471417f6; ?>
<?php unset($__componentOriginal53747ceb358d30c0105769f8471417f6); ?>
<?php endif; ?>
    <?php if (isset($component)) { $__componentOriginal53747ceb358d30c0105769f8471417f6 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal53747ceb358d30c0105769f8471417f6 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.card','data' => ['type' => 'kpi','accent' => 'gold','icon' => 'bi bi-journal-bookmark-fill','label' => 'Classes Today','value' => ''.e($todayClasses->count()).'']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('card'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['type' => 'kpi','accent' => 'gold','icon' => 'bi bi-journal-bookmark-fill','label' => 'Classes Today','value' => ''.e($todayClasses->count()).'']); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal53747ceb358d30c0105769f8471417f6)): ?>
<?php $attributes = $__attributesOriginal53747ceb358d30c0105769f8471417f6; ?>
<?php unset($__attributesOriginal53747ceb358d30c0105769f8471417f6); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal53747ceb358d30c0105769f8471417f6)): ?>
<?php $component = $__componentOriginal53747ceb358d30c0105769f8471417f6; ?>
<?php unset($__componentOriginal53747ceb358d30c0105769f8471417f6); ?>
<?php endif; ?>
</div>


<?php if(isset($atRiskStudents) && $atRiskStudents->count() > 0): ?>
<div class="ent-section ent-mb-lg ent-fade-up ent-delay-2">
    <div class="ent-section-header">
        <div class="ent-section-title">
            <div class="ent-section-title-icon" style="background:rgba(248,113,113,0.12);color:var(--ent-danger);">
                <i class="bi bi-exclamation-triangle-fill"></i>
            </div>
            Early Warning: At-Risk Students
        </div>
    </div>
    <div class="ent-section-body">
        <div class="d-flex gap-3 flex-wrap">
            <?php $__currentLoopData = $atRiskStudents; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $stat): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
            <div style="background:rgba(0,0,0,0.2);border:1px solid rgba(248,113,113,0.15);padding:14px 18px;border-radius:var(--ent-radius-md);min-width:200px;flex:1;">
                <div style="font-size:0.9rem;font-weight:700;color:var(--ent-text);margin-bottom:8px;"><?php echo e($stat->user->name); ?></div>
                <div class="ent-flex-between" style="font-size:0.82rem;color:var(--ent-text-muted);margin-bottom:6px;">
                    <span>Attendance</span>
                    <span style="color:var(--ent-danger);font-weight:700;"><?php echo e($stat->rate); ?>%</span>
                </div>
                <div class="ent-progress">
                    <div class="ent-progress-fill danger" style="width:<?php echo e($stat->rate); ?>%;"></div>
                </div>
            </div>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        </div>
    </div>
</div>
<?php endif; ?>


<div class="row g-4 ent-fade-up ent-delay-3">
    
    <div class="col-lg-8">
        <?php if (isset($component)) { $__componentOriginal53747ceb358d30c0105769f8471417f6 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal53747ceb358d30c0105769f8471417f6 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.card','data' => ['type' => 'section','class' => 'h-100 d-flex flex-column','icon' => 'bi bi-calendar-event-fill']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('card'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['type' => 'section','class' => 'h-100 d-flex flex-column','icon' => 'bi bi-calendar-event-fill']); ?>
             <?php $__env->slot('title', null, []); ?> 
                <div style="display:flex;align-items:center;gap:8px;">
                    <a href="?date=<?php echo e($targetDate->copy()->subDay()->toDateString()); ?>" class="ent-btn ent-btn-xs ent-btn-ghost" style="padding:2px 6px;">
                        <i class="bi bi-chevron-left"></i>
                    </a>
                    <span><?php echo e($targetDate->isToday() ? "Today's Schedule" : $targetDate->format('M d, Y')); ?></span>
                    <a href="?date=<?php echo e($targetDate->copy()->addDay()->toDateString()); ?>" class="ent-btn ent-btn-xs ent-btn-ghost" style="padding:2px 6px;">
                        <i class="bi bi-chevron-right"></i>
                    </a>
                </div>
                <?php if($todayClasses->count() > 0): ?>
                    <?php
                        $completedCount = $todayClasses->where('has_attendance_today', true)->count();
                        $totalCount = $todayClasses->count();
                        $allDone = $completedCount === $totalCount;
                    ?>
                    <span class="ent-badge <?php echo e($allDone ? 'ent-badge-success' : ($completedCount > 0 ? 'ent-badge-warning' : 'ent-badge-neutral')); ?>">
                        <?php echo e($completedCount); ?>/<?php echo e($totalCount); ?> Done
                    </span>
                <?php endif; ?>
             <?php $__env->endSlot(); ?>
            <div style="flex:1;overflow-y:auto;">
                <?php $__empty_1 = true; $__currentLoopData = $todayClasses; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $class): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                    <?php
                        $schedule = $class->schedules->first();
                        $isCompleted = $class->has_attendance_today ?? false;
                        $attendanceCount = $class->attendance_count_today ?? 0;
                        
                        $currentTime = now();
                        $classTime = $schedule ? Carbon\Carbon::today()->setTimeFromTimeString($schedule->start_time) : null;
                        $endTime = $schedule ? Carbon\Carbon::today()->setTimeFromTimeString($schedule->end_time) : null;
                        
                        $statusClass = 'neutral';
                        $statusText = 'Upcoming';
                        $statusIcon = 'bi-clock-fill';
                        
                        if ($isCompleted) {
                            $statusClass = 'success';
                            $statusText = 'Completed';
                            $statusIcon = 'bi-check-circle-fill';
                        } elseif ($classTime && $endTime) {
                            if ($currentTime->greaterThan($endTime)) {
                                $statusClass = 'danger';
                                $statusText = 'Missed';
                                $statusIcon = 'bi-exclamation-circle-fill';
                            } elseif ($currentTime->between($classTime, $endTime)) {
                                $statusClass = 'warning';
                                $statusText = 'Ongoing';
                                $statusIcon = 'bi-play-circle-fill';
                            }
                        }

                        $accentColors = [
                            'success' => '#cfa46f',
                            'warning' => '#fbbf24',
                            'danger' => '#f87171',
                            'neutral' => 'rgba(179,155,130,0.4)',
                        ];
                        $accentColor = $accentColors[$statusClass] ?? $accentColors['neutral'];
                    ?>
                    <div class="class-item" style="display:flex;justify-content:space-between;align-items:center;padding:16px 20px;border-bottom:1px solid rgba(255,255,255,0.03);transition:background 0.15s;<?php echo e($isCompleted ? 'opacity:0.7;' : ''); ?>">
                        <div style="display:flex;align-items:center;gap:14px;">
                            <div style="width:4px;height:44px;border-radius:4px;background:<?php echo e($accentColor); ?>;flex-shrink:0;"></div>
                            <div>
                                <div style="display:flex;align-items:center;gap:8px;margin-bottom:4px;">
                                    <div style="font-size:1rem;font-weight:700;color:var(--ent-text);"><?php echo e($class->name); ?></div>
                                    <span class="ent-badge ent-badge-<?php echo e($statusClass); ?>">
                                        <i class="bi <?php echo e($statusIcon); ?>" style="font-size:0.6rem;"></i> <?php echo e($statusText); ?>

                                    </span>
                                </div>
                                <div style="font-size:0.8rem;color:var(--ent-text-muted);">
                                    <i class="bi bi-tag-fill" style="font-size:0.65rem;margin-right:2px;"></i> <?php echo e($class->code); ?> Â· Year <?php echo e($class->year_level); ?> Sem <?php echo e($class->semester); ?>

                                    <?php if($isCompleted): ?>
                                        <span style="color:#cfa46f;font-weight:600;margin-left:8px;">
                                            Â· <i class="bi bi-people-fill"></i> <?php echo e($attendanceCount); ?> present
                                        </span>
                                        <?php
                                            $health = $class->class_health ?? 0;
                                            $healthColor = $health >= 90 ? '#4ade80' : ($health >= 75 ? '#fbbf24' : '#f87171');
                                        ?>
                                        <span class="ent-badge ent-badge-neutral" style="margin-left:6px;color:<?php echo e($healthColor); ?>;">
                                            <?php echo e($health); ?>% Avg
                                        </span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                        <div style="text-align:right;flex-shrink:0;">
                            <?php if($schedule): ?>
                                <div style="font-size:0.9rem;font-weight:800;color:var(--ent-text);font-variant-numeric:tabular-nums;">
                                    <?php echo e(Carbon\Carbon::parse($schedule->start_time)->format('g:i A')); ?>

                                </div>
                                <div style="font-size:0.72rem;color:var(--ent-text-muted);font-weight:500;">
                                    to <?php echo e(Carbon\Carbon::parse($schedule->end_time)->format('g:i A')); ?>

                                </div>
                            <?php endif; ?>
                            <?php if(!$isCompleted && $statusClass != 'danger'): ?>
                                <a href="<?php echo e(route('teacher.attendance', $class->id)); ?>" class="ent-btn ent-btn-sm ent-btn-secondary" style="margin-top:6px;color:#cfa46f;">
                                    <i class="bi bi-qr-code-scan"></i> Start
                                </a>
                            <?php elseif($isCompleted): ?>
                                <a href="<?php echo e(route('teacher.messages.create', ['subject' => $class->id, 'to' => 'absentees', 'date' => $targetDate->toDateString()])); ?>" class="ent-btn ent-btn-xs ent-btn-ghost" style="margin-top:6px;color:var(--ent-danger);">
                                    <i class="bi bi-envelope-fill"></i> Msg Absentees
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                    <div class="ent-empty" style="padding:60px 20px;">
                        <div class="ent-empty-icon" style="width:72px;height:72px;font-size:2rem;">
                            <i class="bi bi-cup-hot-fill"></i>
                        </div>
                        <div class="ent-empty-title">Free Day!</div>
                        <div class="ent-empty-text">No classes scheduled for today. Enjoy your free time.</div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    
    <div class="col-lg-4">
        <?php if (isset($component)) { $__componentOriginal53747ceb358d30c0105769f8471417f6 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal53747ceb358d30c0105769f8471417f6 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.card','data' => ['type' => 'section','class' => 'h-100 d-flex flex-column','title' => 'Recent Logs']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('card'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['type' => 'section','class' => 'h-100 d-flex flex-column','title' => 'Recent Logs']); ?>
             <?php $__env->slot('icon', null, []); ?> 
                <div class="ent-section-title-icon" style="background:rgba(166,92,59,0.15);color:#e89f7d;">
                    <i class="bi bi-clock-history"></i>
                </div>
             <?php $__env->endSlot(); ?>
             <?php $__env->slot('headerActions', null, []); ?> 
                <input type="text" id="recentLogsSearch" placeholder="Search..." 
                    style="background:rgba(255,255,255,0.06);border:1px solid var(--ent-border);border-radius:var(--ent-radius-xs);color:var(--ent-text);padding:4px 10px;font-size:0.75rem;width:110px;outline:none;font-family:'Inter',sans-serif;" 
                    onkeyup="filterRecentLogs()">
             <?php $__env->endSlot(); ?>
            <div style="flex:1;overflow-y:auto;">
                <?php $__empty_1 = true; $__currentLoopData = $recentAttendance->take(6); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $record): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                    <div class="attendance-row ent-activity-row">
                        <div class="ent-avatar ent-avatar-round" style="width:32px;height:32px;font-size:0.65rem;">
                            <?php echo e(substr($record->user->name, 0, 2)); ?>

                        </div>
                        <div class="ent-activity-body">
                            <div class="ent-activity-name" style="font-size:0.82rem;"><?php echo e($record->user->name); ?></div>
                            <div class="ent-activity-meta"><?php echo e($record->subject->name ?? $record->subject_code); ?></div>
                        </div>
                        <span class="ent-badge ent-badge-<?php echo e(strtolower($record->status) === 'present' ? 'success' : (strtolower($record->status) === 'late' ? 'warning' : 'danger')); ?>">
                            <?php echo e($record->status); ?>

                        </span>
                    </div>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                    <div class="ent-empty" style="padding:60px 20px;">
                        <div class="ent-empty-icon" style="width:56px;height:56px;font-size:1.5rem;">
                            <i class="bi bi-inbox-fill"></i>
                        </div>
                        <div class="ent-empty-title" style="font-size:0.9rem;">No recent logs</div>
                        <div class="ent-empty-text">Records will appear once submitted.</div>
                    </div>
                <?php endif; ?>
            </div>
         <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal53747ceb358d30c0105769f8471417f6)): ?>
<?php $attributes = $__attributesOriginal53747ceb358d30c0105769f8471417f6; ?>
<?php unset($__attributesOriginal53747ceb358d30c0105769f8471417f6); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal53747ceb358d30c0105769f8471417f6)): ?>
<?php $component = $__componentOriginal53747ceb358d30c0105769f8471417f6; ?>
<?php unset($__componentOriginal53747ceb358d30c0105769f8471417f6); ?>
<?php endif; ?>
    </div>
</div>


<?php if(count($weeklyLabels) > 0): ?>
<?php if (isset($component)) { $__componentOriginal53747ceb358d30c0105769f8471417f6 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal53747ceb358d30c0105769f8471417f6 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.card','data' => ['type' => 'section','class' => 'ent-fade-up ent-delay-4','style' => 'margin-top:24px;','icon' => 'bi bi-bar-chart-fill','title' => 'Weekly Attendance Overview']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('card'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['type' => 'section','class' => 'ent-fade-up ent-delay-4','style' => 'margin-top:24px;','icon' => 'bi bi-bar-chart-fill','title' => 'Weekly Attendance Overview']); ?>
    <div class="ent-chart-container" style="height:250px;">
        <canvas id="weeklyChart"></canvas>
    </div>
 <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal53747ceb358d30c0105769f8471417f6)): ?>
<?php $attributes = $__attributesOriginal53747ceb358d30c0105769f8471417f6; ?>
<?php unset($__attributesOriginal53747ceb358d30c0105769f8471417f6); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal53747ceb358d30c0105769f8471417f6)): ?>
<?php $component = $__componentOriginal53747ceb358d30c0105769f8471417f6; ?>
<?php unset($__componentOriginal53747ceb358d30c0105769f8471417f6); ?>
<?php endif; ?>
<?php endif; ?>


<?php
    $hcalStart = \Carbon\Carbon::create($calYear, $calMonth, 1);
    $hcalEnd = $hcalStart->copy()->endOfMonth();
    $hcalPrev = $hcalStart->copy()->subMonth();
    $hcalNext = $hcalStart->copy()->addMonth();
    $hcalStartDow = $hcalStart->dayOfWeek;
    $hcalIsCurrentMonth = (now()->year == $calYear && now()->month == $calMonth);
    $hcalToday = now()->day;
?>

<?php if (isset($component)) { $__componentOriginal53747ceb358d30c0105769f8471417f6 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal53747ceb358d30c0105769f8471417f6 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.card','data' => ['type' => 'section','class' => 'ent-fade-up ent-delay-4','style' => 'margin-top:24px;','title' => 'Holiday & Events Calendar']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('card'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['type' => 'section','class' => 'ent-fade-up ent-delay-4','style' => 'margin-top:24px;','title' => 'Holiday & Events Calendar']); ?>
     <?php $__env->slot('icon', null, []); ?> 
        <div class="ent-section-title-icon" style="background:rgba(248,113,113,0.12);color:#f87171;">
            <i class="bi bi-calendar-heart-fill"></i>
        </div>
     <?php $__env->endSlot(); ?>
     <?php $__env->slot('headerActions', null, []); ?> 
        <button type="button" class="ent-btn ent-btn-sm ent-btn-primary" onclick="openHcalModal()">
            <i class="bi bi-plus-lg"></i> Add Event
        </button>
     <?php $__env->endSlot(); ?>
    
    <div style="padding:16px 20px;">
        <div class="hcal-container">
            
            <div class="hcal-calendar-pane">
                <div class="hcal-nav">
                    <a href="?hcal_year=<?php echo e($hcalPrev->year); ?>&hcal_month=<?php echo e($hcalPrev->month); ?>" class="hcal-nav-btn">
                        <i class="bi bi-chevron-left"></i>
                    </a>
                    <div class="hcal-month-label"><?php echo e($hcalStart->format('F Y')); ?></div>
                    <a href="?hcal_year=<?php echo e($hcalNext->year); ?>&hcal_month=<?php echo e($hcalNext->month); ?>" class="hcal-nav-btn">
                        <i class="bi bi-chevron-right"></i>
                    </a>
                </div>

                <div class="hcal-day-labels">
                    <?php $__currentLoopData = ['S','M','T','W','T','F','S']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $lbl): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <div class="hcal-day-label"><?php echo e($lbl); ?></div>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </div>

                <div class="hcal-grid">
                    <?php for($i = 0; $i < $hcalStartDow; $i++): ?>
                        <div class="hcal-day empty"></div>
                    <?php endfor; ?>

                    <?php for($d = 1; $d <= $hcalEnd->day; $d++): ?>
                        <?php
                            $dateKey = \Carbon\Carbon::create($calYear, $calMonth, $d)->format('Y-m-d');
                            $isToday = $hcalIsCurrentMonth && $d === $hcalToday;
                            $isSunday = \Carbon\Carbon::create($calYear, $calMonth, $d)->dayOfWeek === 0;
                            $dayEvents = $hcalEventsMap[$dateKey] ?? [];
                            $hasEvents = count($dayEvents) > 0;
                            $isHoliday = collect($dayEvents)->where('source', 'holiday')->isNotEmpty();
                            $cls = '';
                            if ($isToday) $cls .= ' today';
                            if ($isSunday) $cls .= ' sunday';
                            if ($hasEvents) $cls .= ' has-event';
                            if ($isHoliday) $cls .= ' holiday-day';
                        ?>
                        <div class="hcal-day<?php echo e($cls); ?>" <?php if($hasEvents): ?> onclick="scrollToEvent('<?php echo e($dateKey); ?>')" title="<?php echo e(collect($dayEvents)->pluck('name')->join(', ')); ?>" <?php endif; ?>>
                            <div class="hcal-day-num"><?php echo e($d); ?></div>
                            <?php if($hasEvents): ?>
                                <div class="hcal-dots">
                                    <?php $__currentLoopData = collect($dayEvents)->unique('type')->take(3); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $evt): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                        <div class="hcal-dot <?php echo e($evt['type']); ?>"></div>
                                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endfor; ?>
                </div>

                <div class="hcal-legend">
                    <div class="hcal-legend-item"><div class="hcal-legend-dot" style="background:#dc2626;"></div> National</div>
                    <div class="hcal-legend-item"><div class="hcal-legend-dot" style="background:#d97706;"></div> Local</div>
                    <div class="hcal-legend-item"><div class="hcal-legend-dot" style="background:#7c2d12;"></div> School</div>
                    <div class="hcal-legend-item"><div class="hcal-legend-dot" style="background:#6366f1;"></div> No Class</div>
                    <div class="hcal-legend-item"><div class="hcal-legend-dot" style="background:#60a5fa;"></div> Announcement</div>
                </div>
            </div>

            
            <div class="hcal-events-pane">
                <div style="font-size:0.72rem;font-weight:700;text-transform:uppercase;letter-spacing:0.06em;color:var(--ent-text-muted);margin-bottom:12px;">
                    <i class="bi bi-calendar-event"></i> Upcoming Events
                </div>
                <?php $__empty_1 = true; $__currentLoopData = $hcalUpcoming; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $evt): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                    <div class="hcal-event-card" data-type="<?php echo e($evt->type); ?>" data-date="<?php echo e(is_object($evt->date) ? $evt->date->format('Y-m-d') : $evt->date); ?>" id="evt-<?php echo e(is_object($evt->date) ? $evt->date->format('Y-m-d') : $evt->date); ?>">
                        <div class="hcal-event-type <?php echo e($evt->type); ?>">
                            <i class="bi <?php echo e($evt->source === 'holiday' ? 'bi-calendar-heart' : 'bi-megaphone'); ?>"></i>
                            <?php echo e($evt->type_label); ?>

                        </div>
                        <div class="hcal-event-name"><?php echo e($evt->name); ?></div>
                        <div class="hcal-event-date">
                            <i class="bi bi-calendar3"></i> <?php echo e($evt->date_formatted); ?>

                            <?php if(isset($evt->author)): ?>
                                Â· <i class="bi bi-person"></i> <?php echo e($evt->author); ?>

                            <?php endif; ?>
                        </div>
                        <?php if($evt->description): ?>
                            <div class="hcal-event-desc"><?php echo e($evt->description); ?></div>
                        <?php endif; ?>

                        <?php if($evt->source === 'holiday'): ?>
                        <div class="hcal-event-actions">
                            <button type="button" class="hcal-event-action-btn" onclick="openHcalEditModal(<?php echo e($evt->id); ?>, '<?php echo e(addslashes($evt->name)); ?>', '<?php echo e($evt->description ? addslashes($evt->description) : ''); ?>', '<?php echo e($evt->type); ?>', '<?php echo e(is_object($evt->date) ? $evt->date->format('Y-m-d') : $evt->date); ?>')">
                                <i class="bi bi-pencil"></i>
                            </button>
                            <form action="<?php echo e(route('teacher.holidays.destroy', $evt->id)); ?>" method="POST" style="display:inline;" onsubmit="return confirm('Delete this holiday?');">
                                <?php echo csrf_field(); ?> <?php echo method_field('DELETE'); ?>
                                <button type="submit" class="hcal-event-action-btn danger"><i class="bi bi-trash3"></i></button>
                            </form>
                        </div>
                        <?php endif; ?>
                    </div>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                    <div class="hcal-empty">
                        <div class="hcal-empty-icon"><i class="bi bi-calendar-x"></i></div>
                        <div class="hcal-empty-text">No upcoming events</div>
                    </div>
                <?php endif; ?>

                <button type="button" class="hcal-add-btn" onclick="openHcalModal()" style="margin-top:8px;">
                    <i class="bi bi-plus-circle"></i> Add Holiday / Event
                </button>
            </div>
        </div>
    </div>
</div>


<div class="hcal-modal-overlay" id="hcalModalOverlay">
    <div class="hcal-modal">
        <div class="hcal-modal-header">
            <div class="hcal-modal-title" id="hcalModalTitle">Add Holiday / Event</div>
            <button type="button" class="hcal-modal-close" onclick="closeHcalModal()">âœ•</button>
        </div>
        <form id="hcalForm" method="POST" action="<?php echo e(route('teacher.holidays.store')); ?>">
            <?php echo csrf_field(); ?>
            <div id="hcalMethodField"></div>
            <div class="hcal-modal-body">
                <div class="hcal-form-group">
                    <label class="hcal-form-label">Event Name *</label>
                    <input type="text" name="name" class="hcal-form-input" id="hcalName" required placeholder="e.g. Independence Day">
                </div>
                <div class="hcal-form-group">
                    <label class="hcal-form-label">Date *</label>
                    <input type="date" name="date" class="hcal-form-input" id="hcalDate" required>
                </div>
                <div class="hcal-form-group">
                    <label class="hcal-form-label">Type *</label>
                    <select name="type" class="hcal-form-select" id="hcalType" required>
                        <option value="national">National Holiday</option>
                        <option value="local">Local Holiday</option>
                        <option value="school">School Holiday</option>
                        <option value="no_class">No Classes</option>
                    </select>
                </div>
                <div class="hcal-form-group" style="margin-bottom:0;">
                    <label class="hcal-form-label">Description</label>
                    <textarea name="description" class="hcal-form-textarea" id="hcalDesc" placeholder="Optional description..."></textarea>
                </div>
            </div>
            <div class="hcal-modal-footer">
                <button type="button" class="hcal-btn-cancel" onclick="closeHcalModal()">Cancel</button>
                <button type="submit" class="hcal-btn-submit" id="hcalSubmitBtn">
                    <i class="bi bi-check-lg"></i> Save Event
                </button>
            </div>
        </form>
    </div>
</div>

<?php $__env->stopSection(); ?>

<?php $__env->startSection('scripts'); ?>
<?php if(count($weeklyLabels) > 0): ?>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
const ctx = document.getElementById('weeklyChart').getContext('2d');
Chart.defaults.color = '#b39b82';
Chart.defaults.font.family = "'Inter', sans-serif";

new Chart(ctx, {
    type: 'bar',
    data: {
        labels: <?php echo json_encode($weeklyLabels, 15, 512) ?>,
        datasets: [{
            label: 'Present',
            data: <?php echo json_encode($weeklyPresent, 15, 512) ?>,
            backgroundColor: 'rgba(74, 222, 128, 0.8)',
            borderColor: 'rgba(74, 222, 128, 1)',
            borderWidth: 1,
            borderRadius: 6,
        }, {
            label: 'Late',
            data: <?php echo json_encode($weeklyLate, 15, 512) ?>,
            backgroundColor: 'rgba(245, 158, 11, 0.8)',
            borderColor: 'rgba(245, 158, 11, 1)',
            borderWidth: 1,
            borderRadius: 6,
        }, {
            label: 'Absent',
            data: <?php echo json_encode($weeklyAbsent, 15, 512) ?>,
            backgroundColor: 'rgba(239, 68, 68, 0.8)',
            borderColor: 'rgba(239, 68, 68, 1)',
            borderWidth: 1,
            borderRadius: 6,
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: {
                position: 'top',
                labels: {
                    usePointStyle: true,
                    boxWidth: 8,
                    padding: 20,
                    font: { weight: '600', size: 11 }
                }
            },
            tooltip: {
                backgroundColor: 'rgba(15, 11, 8, 0.95)',
                titleColor: '#f3e7cd',
                bodyColor: '#e7dcc8',
                borderColor: 'rgba(207,164,111,0.2)',
                borderWidth: 1,
                padding: 12,
                cornerRadius: 10
            }
        },
        scales: {
            y: {
                beginAtZero: true,
                grid: { color: 'rgba(255, 225, 150, 0.04)', drawBorder: false },
                ticks: { stepSize: 1, font: { weight: '500' } }
            },
            x: {
                grid: { display: false, drawBorder: false },
                ticks: { font: { weight: '600' } }
            }
        }
    }
});
</script>
<?php endif; ?>

<script>
// â”€â”€ Skeleton â†’ Content Reveal â”€â”€
(function() {
    var skelKpis = document.getElementById('skelKpis');
    var realKpis = document.getElementById('realKpis');
    if (skelKpis && realKpis) {
        skelKpis.style.display = 'none';
        realKpis.style.display = '';
    }
})();

// Real-time clock
(function() {
    const clockEl = document.getElementById('teacherClock');
    const clockMobileEl = document.getElementById('teacherClockMobile');
    function tick() {
        const now = new Date();
        let h = now.getHours(), m = now.getMinutes(), s = now.getSeconds();
        const ampm = h >= 12 ? 'PM' : 'AM';
        h = h % 12 || 12;
        const full = h + ':' + (m < 10 ? '0' : '') + m + ':' + (s < 10 ? '0' : '') + s + ' ' + ampm;
        const short = h + ':' + (m < 10 ? '0' : '') + m + ' ' + ampm;
        if (clockEl) clockEl.textContent = full;
        if (clockMobileEl) clockMobileEl.textContent = short;
    }
    setInterval(tick, 1000);
    tick();
})();

function filterRecentLogs() {
    const input = document.getElementById('recentLogsSearch').value.toLowerCase();
    const rows = document.querySelectorAll('.attendance-row');
    rows.forEach(row => {
        const text = row.innerText.toLowerCase();
        row.style.display = text.includes(input) ? 'flex' : 'none';
    });
}

function openLeaveDrawer() {
    const html = `
        <div style="color: #e7dcc8;">
            <p>Please provide details for your leave/substitute request. This will be sent to the administration.</p>
            <div class="mb-3">
                <label style="font-size: 0.85rem; color: #b39b82; margin-bottom: 6px;">Date of Leave</label>
                <input type="date" class="tch-input w-100" id="leaveDate">
            </div>
            <div class="mb-3">
                <label style="font-size: 0.85rem; color: #b39b82; margin-bottom: 6px;">Reason</label>
                <textarea class="tch-input w-100" id="leaveReason" rows="3" placeholder="Explain the reason for leave..."></textarea>
            </div>
        </div>
    `;
    if(typeof openDrawer === 'function') {
        openDrawer('Request Leave / Substitute', html, function() {
            if(typeof showPremiumToast === 'function') {
                showPremiumToast('Leave request submitted to Admin successfully!', 'success');
            } else if(typeof showToast === 'function') {
                showToast('Leave request submitted to Admin successfully!', 'success');
            }
            closeDrawer();
        }, 'Submit Request');
    } else {
        if(typeof showPremiumToast === 'function') {
            showPremiumToast("Leave request submitted successfully!", 'success');
        }
    }
}

// â”€â”€ HOLIDAY CALENDAR MODAL â”€â”€
function openHcalModal() {
    document.getElementById('hcalModalTitle').textContent = 'Add Holiday / Event';
    document.getElementById('hcalForm').action = '<?php echo e(route("teacher.holidays.store")); ?>';
    document.getElementById('hcalMethodField').innerHTML = '';
    document.getElementById('hcalName').value = '';
    document.getElementById('hcalDate').value = '';
    document.getElementById('hcalType').value = 'national';
    document.getElementById('hcalDesc').value = '';
    document.getElementById('hcalSubmitBtn').innerHTML = '<i class="bi bi-check-lg"></i> Save Event';
    document.getElementById('hcalModalOverlay').classList.add('active');
}

function openHcalEditModal(id, name, desc, type, date) {
    document.getElementById('hcalModalTitle').textContent = 'Edit Holiday';
    document.getElementById('hcalForm').action = '/teacher/holidays/' + id;
    document.getElementById('hcalMethodField').innerHTML = '<input type="hidden" name="_method" value="PUT">';
    document.getElementById('hcalName').value = name;
    document.getElementById('hcalDate').value = date;
    document.getElementById('hcalType').value = type;
    document.getElementById('hcalDesc').value = desc;
    document.getElementById('hcalSubmitBtn').innerHTML = '<i class="bi bi-check-lg"></i> Update Event';
    document.getElementById('hcalModalOverlay').classList.add('active');
}

function closeHcalModal() {
    document.getElementById('hcalModalOverlay').classList.remove('active');
}

document.getElementById('hcalModalOverlay').addEventListener('click', function(e) {
    if (e.target === this) closeHcalModal();
});
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') closeHcalModal();
});

function scrollToEvent(dateKey) {
    const el = document.getElementById('evt-' + dateKey);
    if (el) {
        el.scrollIntoView({ behavior: 'smooth', block: 'center' });
        el.style.boxShadow = '0 0 0 2px rgba(207,164,111,0.5)';
        setTimeout(() => { el.style.boxShadow = ''; }, 2000);
    }
}
</script>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>