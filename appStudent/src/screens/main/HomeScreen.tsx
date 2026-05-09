import React, {useCallback, useState} from 'react';
import {
  ActivityIndicator,
  RefreshControl,
  ScrollView,
  StatusBar,
  StyleSheet,
  Text,
  TouchableOpacity,
  View,
} from 'react-native';
import {useFocusEffect, useNavigation} from '@react-navigation/native';
import {useAuth} from '../../contexts/AuthContext';
import dashboardService, {
  StudentDashboardAssignmentItem,
  StudentDashboardData,
  StudentDashboardLessonItem,
  StudentDashboardQuizAttemptItem,
} from '../../services/dashboardService';

const HomeScreen: React.FC = () => {
  const {user} = useAuth();
  const navigation = useNavigation<any>();
  const [dashboard, setDashboard] = useState<StudentDashboardData | null>(null);
  const [loading, setLoading] = useState(true);
  const [refreshing, setRefreshing] = useState(false);

  const fetchDashboard = async (isRefresh = false) => {
    if (isRefresh) {
      setRefreshing(true);
    } else {
      setLoading(true);
    }

    try {
      const response = await dashboardService.getStudentDashboard();
      if (response.success) {
        setDashboard(response.data);
      }
    } catch (error) {
      console.error('Error fetching student dashboard', error);
    } finally {
      setLoading(false);
      setRefreshing(false);
    }
  };

  useFocusEffect(
    useCallback(() => {
      fetchDashboard();
    }, []),
  );

  const renderAssignment = (item: StudentDashboardAssignmentItem) => (
    <View key={item.assignment_id} style={styles.listCard}>
      <View style={styles.listCardHeader}>
        <Text style={styles.listCardTitle}>{item.title}</Text>
        <View
          style={[
            styles.badge,
            item.status === 'pending' ? styles.badgeWarning : styles.badgeSuccess,
          ]}>
          <Text
            style={[
              styles.badgeText,
              item.status === 'pending'
                ? styles.badgeTextWarning
                : styles.badgeTextSuccess,
            ]}>
            {item.status === 'pending' ? 'Cần nộp' : 'Đã nộp'}
          </Text>
        </View>
      </View>
      <Text style={styles.listMeta}>{item.class_name || 'Không rõ lớp'}</Text>
      <Text style={styles.listSubMeta}>
        Hạn nộp: {item.due_date ? new Date(item.due_date).toLocaleString('vi-VN') : 'Chưa có'}
      </Text>
    </View>
  );

  const renderLesson = (item: StudentDashboardLessonItem) => (
    <TouchableOpacity
      key={item.lesson_id}
      style={styles.listCard}
      onPress={() => navigation.navigate('LessonDetail', {lessonId: item.lesson_id})}>
      <View style={styles.listCardHeader}>
        <Text style={styles.listCardTitle}>{item.title}</Text>
        <Text style={styles.progressText}>{Math.round(item.progress_percent)}%</Text>
      </View>
      <Text style={styles.listMeta}>{item.class_name || 'Không rõ lớp'}</Text>
      <View style={styles.progressBarTrack}>
        <View style={[styles.progressBarFill, {width: `${Math.min(item.progress_percent, 100)}%`}]} />
      </View>
    </TouchableOpacity>
  );

  const renderQuizAttempt = (item: StudentDashboardQuizAttemptItem) => (
    <View key={item.attempt_id} style={styles.listCard}>
      <View style={styles.listCardHeader}>
        <Text style={styles.listCardTitle}>{item.quiz_title || 'Quiz'}</Text>
        <Text style={styles.scoreText}>
          {item.percentage !== null ? `${Math.round(item.percentage)}%` : '--'}
        </Text>
      </View>
      <Text style={styles.listMeta}>{item.lesson_title || 'Không rõ bài học'}</Text>
      <Text style={styles.listSubMeta}>{item.class_name || 'Không rõ lớp'}</Text>
    </View>
  );

  return (
    <View style={styles.container}>
      <StatusBar barStyle="light-content" backgroundColor="#0D47A1" />
      <View style={styles.header}>
        <Text style={styles.greeting}>Xin chào</Text>
        <Text style={styles.userName}>{user?.name}</Text>
        <Text style={styles.subtitle}>Tổng quan học tập của bạn</Text>
      </View>

      <ScrollView
        style={styles.content}
        refreshControl={
          <RefreshControl
            refreshing={refreshing}
            onRefresh={() => fetchDashboard(true)}
            colors={['#0D47A1']}
          />
        }>
        {loading ? (
          <View style={styles.loadingBox}>
            <ActivityIndicator size="large" color="#0D47A1" />
          </View>
        ) : (
          <>
            <View style={styles.statsGrid}>
              <View style={styles.statCard}>
                <Text style={styles.statValue}>{dashboard?.stats.active_classes || 0}</Text>
                <Text style={styles.statLabel}>Lớp đang học</Text>
              </View>
              <View style={styles.statCard}>
                <Text style={styles.statValue}>{dashboard?.stats.pending_assignments || 0}</Text>
                <Text style={styles.statLabel}>Bài tập cần nộp</Text>
              </View>
              <View style={styles.statCard}>
                <Text style={styles.statValue}>
                  {dashboard?.stats.completed_lessons || 0}/{dashboard?.stats.total_lessons || 0}
                </Text>
                <Text style={styles.statLabel}>Tiến độ bài học</Text>
              </View>
              <View style={styles.statCard}>
                <Text style={styles.statValue}>
                  {dashboard?.stats.average_quiz_score !== null &&
                  dashboard?.stats.average_quiz_score !== undefined
                    ? `${Math.round(dashboard.stats.average_quiz_score)}%`
                    : '--'}
                </Text>
                <Text style={styles.statLabel}>Điểm quiz TB</Text>
              </View>
            </View>

            <View style={styles.section}>
              <View style={styles.sectionHeader}>
                <Text style={styles.sectionTitle}>Lớp đang học</Text>
                <TouchableOpacity onPress={() => navigation.navigate('ClassesTab')}>
                  <Text style={styles.sectionLink}>Xem tất cả</Text>
                </TouchableOpacity>
              </View>
              {dashboard?.active_classes.length ? (
                dashboard.active_classes.map(item => (
                  <TouchableOpacity
                    key={item.id}
                    style={styles.listCard}
                    onPress={() => navigation.navigate('ClassDetail', {classId: item.id})}>
                    <Text style={styles.listCardTitle}>{item.name}</Text>
                    <Text style={styles.listMeta}>{item.teacher_name || 'Không rõ giáo viên'}</Text>
                    <Text style={styles.listSubMeta}>{item.semester || 'Chưa có học kỳ'}</Text>
                  </TouchableOpacity>
                ))
              ) : (
                <View style={styles.emptyCard}>
                  <Text style={styles.emptyText}>Bạn chưa có lớp học nào đang hoạt động.</Text>
                </View>
              )}
            </View>

            <View style={styles.section}>
              <Text style={styles.sectionTitle}>Bài tập sắp tới hạn</Text>
              {dashboard?.upcoming_assignments.length ? (
                dashboard.upcoming_assignments.map(renderAssignment)
              ) : (
                <View style={styles.emptyCard}>
                  <Text style={styles.emptyText}>Không có bài tập sắp tới hạn.</Text>
                </View>
              )}
            </View>

            <View style={styles.section}>
              <Text style={styles.sectionTitle}>Bài học gần đây</Text>
              {dashboard?.recent_lessons.length ? (
                dashboard.recent_lessons.map(renderLesson)
              ) : (
                <View style={styles.emptyCard}>
                  <Text style={styles.emptyText}>Chưa có dữ liệu tiến độ bài học.</Text>
                </View>
              )}
            </View>

            <View style={styles.section}>
              <Text style={styles.sectionTitle}>Kết quả quiz gần đây</Text>
              {dashboard?.recent_quiz_attempts.length ? (
                dashboard.recent_quiz_attempts.map(renderQuizAttempt)
              ) : (
                <View style={styles.emptyCard}>
                  <Text style={styles.emptyText}>Bạn chưa làm quiz nào gần đây.</Text>
                </View>
              )}
            </View>

            {dashboard?.pending_classes.length ? (
              <View style={styles.section}>
                <Text style={styles.sectionTitle}>Yêu cầu tham gia đang chờ duyệt</Text>
                {dashboard.pending_classes.map(item => (
                  <View key={item.id} style={styles.listCard}>
                    <Text style={styles.listCardTitle}>{item.name}</Text>
                    <Text style={styles.listMeta}>{item.teacher_name || 'Không rõ giáo viên'}</Text>
                  </View>
                ))}
              </View>
            ) : null}

            <View style={styles.bottomSpace} />
          </>
        )}
      </ScrollView>
    </View>
  );
};

const styles = StyleSheet.create({
  container: {
    flex: 1,
    backgroundColor: '#F1F5F9',
  },
  header: {
    backgroundColor: '#0D47A1',
    paddingHorizontal: 20,
    paddingTop: 18,
    paddingBottom: 22,
  },
  greeting: {
    color: '#BFDBFE',
    fontSize: 14,
  },
  userName: {
    color: '#FFFFFF',
    fontSize: 26,
    fontWeight: '700',
    marginTop: 4,
  },
  subtitle: {
    color: '#DBEAFE',
    marginTop: 6,
  },
  content: {
    flex: 1,
  },
  loadingBox: {
    paddingVertical: 60,
    alignItems: 'center',
  },
  statsGrid: {
    flexDirection: 'row',
    flexWrap: 'wrap',
    paddingHorizontal: 14,
    marginTop: 16,
    gap: 12,
  },
  statCard: {
    width: '47%',
    backgroundColor: '#FFFFFF',
    borderRadius: 12,
    padding: 16,
  },
  statValue: {
    fontSize: 24,
    fontWeight: '700',
    color: '#0F172A',
  },
  statLabel: {
    marginTop: 6,
    color: '#64748B',
    fontSize: 13,
  },
  section: {
    marginTop: 20,
    paddingHorizontal: 16,
  },
  sectionHeader: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'center',
    marginBottom: 10,
  },
  sectionTitle: {
    fontSize: 18,
    fontWeight: '700',
    color: '#0F172A',
    marginBottom: 10,
  },
  sectionLink: {
    color: '#1D4ED8',
    fontWeight: '600',
  },
  listCard: {
    backgroundColor: '#FFFFFF',
    borderRadius: 12,
    padding: 14,
    marginBottom: 10,
  },
  listCardHeader: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'center',
    gap: 12,
  },
  listCardTitle: {
    flex: 1,
    fontSize: 15,
    fontWeight: '700',
    color: '#0F172A',
  },
  listMeta: {
    marginTop: 4,
    color: '#475569',
  },
  listSubMeta: {
    marginTop: 2,
    color: '#64748B',
    fontSize: 12,
  },
  badge: {
    borderRadius: 999,
    paddingHorizontal: 10,
    paddingVertical: 5,
  },
  badgeWarning: {
    backgroundColor: '#FEF3C7',
  },
  badgeSuccess: {
    backgroundColor: '#DCFCE7',
  },
  badgeText: {
    fontSize: 12,
    fontWeight: '700',
  },
  badgeTextWarning: {
    color: '#92400E',
  },
  badgeTextSuccess: {
    color: '#166534',
  },
  progressBarTrack: {
    marginTop: 10,
    height: 8,
    borderRadius: 999,
    backgroundColor: '#E2E8F0',
    overflow: 'hidden',
  },
  progressBarFill: {
    height: '100%',
    backgroundColor: '#2563EB',
    borderRadius: 999,
  },
  progressText: {
    color: '#1D4ED8',
    fontWeight: '700',
  },
  scoreText: {
    color: '#166534',
    fontWeight: '700',
  },
  emptyCard: {
    backgroundColor: '#FFFFFF',
    borderRadius: 12,
    padding: 18,
  },
  emptyText: {
    color: '#64748B',
    textAlign: 'center',
  },
  bottomSpace: {
    height: 30,
  },
});

export default HomeScreen;
