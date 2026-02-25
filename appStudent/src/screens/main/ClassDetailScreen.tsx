import React, {useState, useEffect} from 'react';
import {
  View,
  Text,
  StyleSheet,
  ScrollView,
  TouchableOpacity,
  ActivityIndicator,
  StatusBar,
  RefreshControl,
} from 'react-native';
import {useRoute, useNavigation, RouteProp} from '@react-navigation/native';
import classService, {ClassInfo} from '../../services/classService';
import {MainStackParamList} from '../../navigation/MainNavigator';

type ClassDetailRouteProp = RouteProp<MainStackParamList, 'ClassDetail'>;

type TabKey = 'info' | 'lessons' | 'students';

const ClassDetailScreen: React.FC = () => {
  const route = useRoute<ClassDetailRouteProp>();
  const navigation = useNavigation();
  const {classId} = route.params;

  const [classData, setClassData] = useState<ClassInfo | null>(null);
  const [loading, setLoading] = useState(true);
  const [refreshing, setRefreshing] = useState(false);
  const [activeTab, setActiveTab] = useState<TabKey>('info');

  const fetchDetail = async (isRefresh = false) => {
    if (isRefresh) {
      setRefreshing(true);
    } else {
      setLoading(true);
    }
    try {
      const response = await classService.getClassDetail(classId);
      if (response.success) {
        setClassData(response.data);
      }
    } catch (error: any) {
      console.error('Error fetching class detail:', error);
    } finally {
      setLoading(false);
      setRefreshing(false);
    }
  };

  useEffect(() => {
    fetchDetail();
  }, [classId]);

  const tabs: {key: TabKey; label: string; count?: number}[] = [
    {key: 'info', label: 'Thông tin'},
    {key: 'lessons', label: 'Bài học', count: classData?.lessons?.length || 0},
    {
      key: 'students',
      label: 'Thành viên',
      count: classData?.enrollment?.length || 0,
    },
  ];

  if (loading) {
    return (
      <View style={styles.loadingContainer}>
        <ActivityIndicator size="large" color="#2563EB" />
      </View>
    );
  }

  if (!classData) {
    return (
      <View style={styles.errorContainer}>
        <Text style={styles.errorIcon}>😔</Text>
        <Text style={styles.errorText}>Không thể tải thông tin lớp học</Text>
        <TouchableOpacity style={styles.retryButton} onPress={() => fetchDetail()}>
          <Text style={styles.retryButtonText}>Thử lại</Text>
        </TouchableOpacity>
      </View>
    );
  }

  return (
    <View style={styles.container}>
      <StatusBar barStyle="light-content" backgroundColor="#2563EB" />

      {/* Header */}
      <View style={styles.header}>
        <TouchableOpacity
          style={styles.backButton}
          onPress={() => navigation.goBack()}>
          <Text style={styles.backIcon}>←</Text>
        </TouchableOpacity>
        <View style={styles.headerContent}>
          <Text style={styles.headerTitle} numberOfLines={1}>
            {classData.name}
          </Text>
          <Text style={styles.headerSubtitle}>
            {classData.semester || 'Chưa xác định'} • Mã: {classData.code}
          </Text>
        </View>
      </View>

      {/* Tabs */}
      <View style={styles.tabBar}>
        {tabs.map(tab => (
          <TouchableOpacity
            key={tab.key}
            style={[styles.tab, activeTab === tab.key && styles.tabActive]}
            onPress={() => setActiveTab(tab.key)}>
            <Text
              style={[
                styles.tabText,
                activeTab === tab.key && styles.tabTextActive,
              ]}>
              {tab.label}
            </Text>
            {tab.count !== undefined && tab.count > 0 && (
              <View
                style={[
                  styles.tabBadge,
                  activeTab === tab.key && styles.tabBadgeActive,
                ]}>
                <Text
                  style={[
                    styles.tabBadgeText,
                    activeTab === tab.key && styles.tabBadgeTextActive,
                  ]}>
                  {tab.count}
                </Text>
              </View>
            )}
          </TouchableOpacity>
        ))}
      </View>

      {/* Tab Content */}
      <ScrollView
        style={styles.content}
        refreshControl={
          <RefreshControl
            refreshing={refreshing}
            onRefresh={() => fetchDetail(true)}
            colors={['#2563EB']}
          />
        }
        showsVerticalScrollIndicator={false}>
        {activeTab === 'info' && renderInfoTab(classData)}
        {activeTab === 'lessons' && renderLessonsTab(classData)}
        {activeTab === 'students' && renderStudentsTab(classData)}
        <View style={styles.bottomSpace} />
      </ScrollView>
    </View>
  );
};

// ========== Tab: Thông tin ==========
const renderInfoTab = (classData: ClassInfo) => (
  <View style={styles.tabContent}>
    {/* Teacher Card */}
    <View style={styles.sectionCard}>
      <Text style={styles.sectionTitle}>👨‍🏫 Giáo viên</Text>
      <View style={styles.teacherCard}>
        <View style={styles.teacherAvatar}>
          <Text style={styles.teacherAvatarText}>
            {classData.teacher?.name?.charAt(0)?.toUpperCase() || '?'}
          </Text>
        </View>
        <View style={styles.teacherInfo}>
          <Text style={styles.teacherNameText}>
            {classData.teacher?.name || 'Chưa rõ'}
          </Text>
          <Text style={styles.teacherEmail}>
            {classData.teacher?.email || ''}
          </Text>
        </View>
      </View>
    </View>

    {/* Class Info */}
    <View style={styles.sectionCard}>
      <Text style={styles.sectionTitle}>📋 Thông tin lớp</Text>

      <View style={styles.infoRow}>
        <Text style={styles.infoLabel}>Mã lớp</Text>
        <Text style={styles.infoValueCode}>{classData.code}</Text>
      </View>

      <View style={styles.infoDivider} />

      <View style={styles.infoRow}>
        <Text style={styles.infoLabel}>Học kỳ</Text>
        <Text style={styles.infoValue}>
          {classData.semester || 'Chưa xác định'}
        </Text>
      </View>

      <View style={styles.infoDivider} />

      <View style={styles.infoRow}>
        <Text style={styles.infoLabel}>Trạng thái</Text>
        <View style={styles.statusActiveBadge}>
          <Text style={styles.statusActiveText}>
            {classData.status === 'active' ? 'Đang hoạt động' : classData.status}
          </Text>
        </View>
      </View>

      <View style={styles.infoDivider} />

      <View style={styles.infoRow}>
        <Text style={styles.infoLabel}>Số học sinh</Text>
        <Text style={styles.infoValue}>
          {classData.student_count || 0}
          {classData.max_students ? ` / ${classData.max_students}` : ''}
        </Text>
      </View>

      <View style={styles.infoDivider} />

      <View style={styles.infoRow}>
        <Text style={styles.infoLabel}>Số bài học</Text>
        <Text style={styles.infoValue}>{classData.lesson_count || 0}</Text>
      </View>
    </View>

    {/* Description */}
    {classData.description && (
      <View style={styles.sectionCard}>
        <Text style={styles.sectionTitle}>📝 Mô tả</Text>
        <Text style={styles.descriptionText}>{classData.description}</Text>
      </View>
    )}
  </View>
);

// ========== Tab: Bài học ==========
const renderLessonsTab = (classData: ClassInfo) => {
  const lessons = classData.lessons || [];

  if (lessons.length === 0) {
    return (
      <View style={styles.emptyTab}>
        <Text style={styles.emptyTabIcon}>📖</Text>
        <Text style={styles.emptyTabTitle}>Chưa có bài học</Text>
        <Text style={styles.emptyTabSubtitle}>
          Giáo viên chưa đăng bài học nào cho lớp này
        </Text>
      </View>
    );
  }

  return (
    <View style={styles.tabContent}>
      {lessons.map((lesson, index) => (
        <TouchableOpacity key={lesson.id} style={styles.lessonCard} activeOpacity={0.7}>
          <View style={styles.lessonNumber}>
            <Text style={styles.lessonNumberText}>{index + 1}</Text>
          </View>
          <View style={styles.lessonContent}>
            <Text style={styles.lessonTitle} numberOfLines={2}>
              {lesson.title}
            </Text>
            {lesson.description && (
              <Text style={styles.lessonDescription} numberOfLines={2}>
                {lesson.description}
              </Text>
            )}
          </View>
          <Text style={styles.lessonArrow}>›</Text>
        </TouchableOpacity>
      ))}
    </View>
  );
};

// ========== Tab: Thành viên ==========
const renderStudentsTab = (classData: ClassInfo) => {
  const enrollments = classData.enrollment || [];

  if (enrollments.length === 0) {
    return (
      <View style={styles.emptyTab}>
        <Text style={styles.emptyTabIcon}>👥</Text>
        <Text style={styles.emptyTabTitle}>Chưa có thành viên</Text>
        <Text style={styles.emptyTabSubtitle}>
          Chưa có học sinh nào trong lớp
        </Text>
      </View>
    );
  }

  return (
    <View style={styles.tabContent}>
      {/* Teacher */}
      <View style={styles.memberCard}>
        <View style={[styles.memberAvatar, styles.memberAvatarTeacher]}>
          <Text style={styles.memberAvatarText}>
            {classData.teacher?.name?.charAt(0)?.toUpperCase() || '?'}
          </Text>
        </View>
        <View style={styles.memberInfo}>
          <Text style={styles.memberName}>{classData.teacher?.name}</Text>
          <Text style={styles.memberRole}>Giáo viên</Text>
        </View>
      </View>

      {/* Students */}
      {enrollments.map((enrollment, index) => (
        <View key={enrollment.id} style={styles.memberCard}>
          <View style={styles.memberAvatar}>
            <Text style={styles.memberAvatarText}>
              {enrollment.user?.name?.charAt(0)?.toUpperCase() || '?'}
            </Text>
          </View>
          <View style={styles.memberInfo}>
            <Text style={styles.memberName}>{enrollment.user?.name}</Text>
            <Text style={styles.memberRole}>Học sinh</Text>
          </View>
          <Text style={styles.memberIndex}>#{index + 1}</Text>
        </View>
      ))}
    </View>
  );
};

const styles = StyleSheet.create({
  container: {
    flex: 1,
    backgroundColor: '#F3F4F6',
  },
  loadingContainer: {
    flex: 1,
    justifyContent: 'center',
    alignItems: 'center',
    backgroundColor: '#F3F4F6',
  },
  errorContainer: {
    flex: 1,
    justifyContent: 'center',
    alignItems: 'center',
    backgroundColor: '#F3F4F6',
    padding: 40,
  },
  errorIcon: {
    fontSize: 48,
    marginBottom: 16,
  },
  errorText: {
    fontSize: 16,
    color: '#6B7280',
    textAlign: 'center',
    marginBottom: 20,
  },
  retryButton: {
    backgroundColor: '#2563EB',
    paddingHorizontal: 24,
    paddingVertical: 12,
    borderRadius: 10,
  },
  retryButtonText: {
    color: '#FFFFFF',
    fontSize: 15,
    fontWeight: '600',
  },
  // Header
  header: {
    backgroundColor: '#2563EB',
    flexDirection: 'row',
    alignItems: 'center',
    paddingTop: 12,
    paddingBottom: 16,
    paddingHorizontal: 16,
  },
  backButton: {
    width: 36,
    height: 36,
    borderRadius: 18,
    backgroundColor: 'rgba(255,255,255,0.2)',
    justifyContent: 'center',
    alignItems: 'center',
    marginRight: 12,
  },
  backIcon: {
    fontSize: 20,
    color: '#FFFFFF',
    fontWeight: '700',
  },
  headerContent: {
    flex: 1,
  },
  headerTitle: {
    fontSize: 18,
    fontWeight: '700',
    color: '#FFFFFF',
  },
  headerSubtitle: {
    fontSize: 13,
    color: 'rgba(255,255,255,0.8)',
    marginTop: 2,
  },
  // Tab Bar
  tabBar: {
    flexDirection: 'row',
    backgroundColor: '#FFFFFF',
    borderBottomWidth: 1,
    borderBottomColor: '#E5E7EB',
  },
  tab: {
    flex: 1,
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'center',
    paddingVertical: 14,
    borderBottomWidth: 2,
    borderBottomColor: 'transparent',
    gap: 6,
  },
  tabActive: {
    borderBottomColor: '#2563EB',
  },
  tabText: {
    fontSize: 14,
    fontWeight: '500',
    color: '#6B7280',
  },
  tabTextActive: {
    color: '#2563EB',
    fontWeight: '600',
  },
  tabBadge: {
    backgroundColor: '#E5E7EB',
    paddingHorizontal: 8,
    paddingVertical: 2,
    borderRadius: 10,
  },
  tabBadgeActive: {
    backgroundColor: '#DBEAFE',
  },
  tabBadgeText: {
    fontSize: 12,
    fontWeight: '600',
    color: '#6B7280',
  },
  tabBadgeTextActive: {
    color: '#2563EB',
  },
  // Content
  content: {
    flex: 1,
  },
  tabContent: {
    padding: 16,
    gap: 12,
  },
  bottomSpace: {
    height: 24,
  },
  // Section Card
  sectionCard: {
    backgroundColor: '#FFFFFF',
    borderRadius: 16,
    padding: 16,
    shadowColor: '#000',
    shadowOffset: {width: 0, height: 1},
    shadowOpacity: 0.04,
    shadowRadius: 4,
    elevation: 1,
  },
  sectionTitle: {
    fontSize: 16,
    fontWeight: '700',
    color: '#1F2937',
    marginBottom: 14,
  },
  // Teacher Card (in info tab)
  teacherCard: {
    flexDirection: 'row',
    alignItems: 'center',
  },
  teacherAvatar: {
    width: 48,
    height: 48,
    borderRadius: 24,
    backgroundColor: '#DBEAFE',
    justifyContent: 'center',
    alignItems: 'center',
    marginRight: 14,
  },
  teacherAvatarText: {
    fontSize: 20,
    fontWeight: '700',
    color: '#2563EB',
  },
  teacherInfo: {
    flex: 1,
  },
  teacherNameText: {
    fontSize: 16,
    fontWeight: '600',
    color: '#1F2937',
    marginBottom: 2,
  },
  teacherEmail: {
    fontSize: 13,
    color: '#6B7280',
  },
  // Info rows
  infoRow: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'center',
    paddingVertical: 4,
  },
  infoLabel: {
    fontSize: 14,
    color: '#6B7280',
  },
  infoValue: {
    fontSize: 14,
    color: '#1F2937',
    fontWeight: '500',
  },
  infoValueCode: {
    fontSize: 15,
    color: '#2563EB',
    fontWeight: '700',
    fontFamily: 'monospace',
    letterSpacing: 2,
  },
  infoDivider: {
    height: 1,
    backgroundColor: '#F3F4F6',
    marginVertical: 8,
  },
  statusActiveBadge: {
    backgroundColor: '#D1FAE5',
    borderRadius: 8,
    paddingHorizontal: 10,
    paddingVertical: 3,
  },
  statusActiveText: {
    color: '#065F46',
    fontSize: 13,
    fontWeight: '600',
  },
  descriptionText: {
    fontSize: 14,
    color: '#4B5563',
    lineHeight: 22,
  },
  // Lessons
  lessonCard: {
    backgroundColor: '#FFFFFF',
    borderRadius: 14,
    padding: 14,
    flexDirection: 'row',
    alignItems: 'center',
    shadowColor: '#000',
    shadowOffset: {width: 0, height: 1},
    shadowOpacity: 0.04,
    shadowRadius: 4,
    elevation: 1,
  },
  lessonNumber: {
    width: 36,
    height: 36,
    borderRadius: 10,
    backgroundColor: '#EEF2FF',
    justifyContent: 'center',
    alignItems: 'center',
    marginRight: 12,
  },
  lessonNumberText: {
    fontSize: 15,
    fontWeight: '700',
    color: '#4F46E5',
  },
  lessonContent: {
    flex: 1,
  },
  lessonTitle: {
    fontSize: 15,
    fontWeight: '600',
    color: '#1F2937',
    marginBottom: 2,
  },
  lessonDescription: {
    fontSize: 13,
    color: '#6B7280',
    lineHeight: 18,
  },
  lessonArrow: {
    fontSize: 22,
    color: '#9CA3AF',
    fontWeight: '300',
    marginLeft: 8,
  },
  // Members
  memberCard: {
    backgroundColor: '#FFFFFF',
    borderRadius: 14,
    padding: 14,
    flexDirection: 'row',
    alignItems: 'center',
    shadowColor: '#000',
    shadowOffset: {width: 0, height: 1},
    shadowOpacity: 0.04,
    shadowRadius: 4,
    elevation: 1,
  },
  memberAvatar: {
    width: 40,
    height: 40,
    borderRadius: 20,
    backgroundColor: '#D1FAE5',
    justifyContent: 'center',
    alignItems: 'center',
    marginRight: 12,
  },
  memberAvatarTeacher: {
    backgroundColor: '#DBEAFE',
  },
  memberAvatarText: {
    fontSize: 16,
    fontWeight: '700',
    color: '#065F46',
  },
  memberInfo: {
    flex: 1,
  },
  memberName: {
    fontSize: 15,
    fontWeight: '600',
    color: '#1F2937',
    marginBottom: 1,
  },
  memberRole: {
    fontSize: 13,
    color: '#6B7280',
  },
  memberIndex: {
    fontSize: 13,
    color: '#9CA3AF',
    fontWeight: '500',
  },
  // Empty tab
  emptyTab: {
    alignItems: 'center',
    justifyContent: 'center',
    paddingVertical: 60,
    paddingHorizontal: 40,
  },
  emptyTabIcon: {
    fontSize: 48,
    marginBottom: 16,
  },
  emptyTabTitle: {
    fontSize: 17,
    fontWeight: '600',
    color: '#1F2937',
    marginBottom: 6,
  },
  emptyTabSubtitle: {
    fontSize: 14,
    color: '#9CA3AF',
    textAlign: 'center',
    lineHeight: 20,
  },
});

export default ClassDetailScreen;
